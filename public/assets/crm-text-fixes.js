(function () {
  var markers = /(?:\u00c2|\u00c3|\u00c5|\u00e2|\u00ef|\u00ff|\u0192|\u20ac|\u201a|\u201e|\u2026|\u2020|\u2021|\u02c6|\u2030|\u0160|\u0152|\u017d|\u2018|\u2019|\u201c|\u201d|\u2022|\u2013|\u2014|\u02dc|\u2122|\ufffd)/;
  var win1252 = {
    '\u20ac': 0x80,
    '\u201a': 0x82,
    '\u0192': 0x83,
    '\u201e': 0x84,
    '\u2026': 0x85,
    '\u2020': 0x86,
    '\u2021': 0x87,
    '\u02c6': 0x88,
    '\u2030': 0x89,
    '\u0160': 0x8a,
    '\u2039': 0x8b,
    '\u0152': 0x8c,
    '\u017d': 0x8e,
    '\u2018': 0x91,
    '\u2019': 0x92,
    '\u201c': 0x93,
    '\u201d': 0x94,
    '\u2022': 0x95,
    '\u2013': 0x96,
    '\u2014': 0x97,
    '\u02dc': 0x98,
    '\u2122': 0x99,
    '\u0161': 0x9a,
    '\u203a': 0x9b,
    '\u0153': 0x9c,
    '\u017e': 0x9e,
    '\u0178': 0x9f,
  };

  function byteForChar(char) {
    var code = char.charCodeAt(0);
    if (code <= 255) {
      return code;
    }
    return win1252[char];
  }

  function markerCount(value) {
    var match = String(value || '').match(new RegExp(markers.source, 'g'));
    return match ? match.length : 0;
  }

  function decodeOnce(value) {
    var encoded = '';
    for (var index = 0; index < value.length; index += 1) {
      var byte = byteForChar(value[index]);
      if (byte == null) {
        return null;
      }
      encoded += '%' + byte.toString(16).padStart(2, '0');
    }

    try {
      return decodeURIComponent(encoded);
    } catch (error) {
      return null;
    }
  }

  function decodeMojibake(value) {
    if (!markers.test(value)) {
      return value;
    }

    var best = value;
    var current = value;

    for (var pass = 0; pass < 4; pass += 1) {
      var decoded = decodeOnce(current);
      if (!decoded || decoded === current) {
        break;
      }

      if (markerCount(decoded) <= markerCount(best)) {
        best = decoded;
      }

      current = decoded;
    }

    return best;
  }

  function fixedText(value) {
    var decoded = decodeMojibake(value);
    return decoded
      .replace(/Se d\u00c3\u00a9connecter/g, 'Se d\u00e9connecter')
      .replace(/Page non trouv\u00c3\u00a9e/g, 'Page non trouv\u00e9e')
      .replace(/n\u00e2\u20ac\u2122existe/g, 'n\u2019existe')
      .replace(/d\u00c3\u00a9plac\u00c3\u00a9e/g, 'd\u00e9plac\u00e9e')
      .replace(/Aller \u00c3\u00a0 l\u00e2\u20ac\u2122accueil/g, 'Aller \u00e0 l\u2019accueil')
      .replace(/Cr\u00c3\u00a9ation/g, 'Cr\u00e9ation')
      .replace(/Fran\u00c3\u00a7ais/g, 'Fran\u00e7ais');
  }

  function shouldSkip(node) {
    var parent = node.parentElement;
    if (!parent) {
      return true;
    }

    return ['SCRIPT', 'STYLE', 'TEXTAREA', 'INPUT'].indexOf(parent.tagName) !== -1;
  }

  function fixTextNodes(root) {
    if (!root || !root.querySelectorAll) {
      return;
    }

    var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
      acceptNode: function (node) {
        return !shouldSkip(node) && markers.test(node.nodeValue || '')
          ? NodeFilter.FILTER_ACCEPT
          : NodeFilter.FILTER_REJECT;
      },
    });

    var nodes = [];
    while (walker.nextNode()) {
      nodes.push(walker.currentNode);
    }

    nodes.forEach(function (node) {
      var next = fixedText(node.nodeValue || '');
      if (next !== node.nodeValue) {
        node.nodeValue = next;
      }
    });
  }

  function fixAttributes(root) {
    if (!root || !root.querySelectorAll) {
      return;
    }

    root.querySelectorAll('[title],[aria-label],[placeholder]').forEach(function (element) {
      ['title', 'aria-label', 'placeholder'].forEach(function (name) {
        var value = element.getAttribute(name);
        if (value && markers.test(value)) {
          element.setAttribute(name, fixedText(value));
        }
      });
    });
  }

  function run() {
    fixTextNodes(document.body);
    fixAttributes(document.body);
  }

  document.addEventListener('DOMContentLoaded', function () {
    run();

    var timer = null;
    var observer = new MutationObserver(function () {
      window.clearTimeout(timer);
      timer = window.setTimeout(run, 40);
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true,
      characterData: true,
    });
  });
})();
