package fr.martinsols.crm;

import android.graphics.Color;
import android.graphics.Bitmap;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.view.Window;
import android.webkit.CookieManager;
import android.webkit.WebSettings;
import android.webkit.WebResourceRequest;
import android.webkit.WebView;

import androidx.core.view.WindowCompat;

import com.getcapacitor.Bridge;
import com.getcapacitor.BridgeActivity;
import com.getcapacitor.BridgeWebViewClient;

public class MainActivity extends BridgeActivity {
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        configureIntegratedWebView();
    }

    private void configureIntegratedWebView() {
        WebView webView = getBridge().getWebView();

        if (webView == null) {
            return;
        }

        configureSystemBars(webView);
        getBridge().setWebViewClient(new CrmWebViewClient(getBridge()));

        WebSettings settings = webView.getSettings();
        settings.setDomStorageEnabled(true);
        settings.setDatabaseEnabled(true);
        settings.setGeolocationEnabled(true);
        settings.setJavaScriptCanOpenWindowsAutomatically(true);
        settings.setMediaPlaybackRequiresUserGesture(false);

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            settings.setSafeBrowsingEnabled(true);
        }

        CookieManager cookieManager = CookieManager.getInstance();
        cookieManager.setAcceptCookie(true);

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
            cookieManager.setAcceptThirdPartyCookies(webView, true);
        }
    }

    private void configureSystemBars(WebView webView) {
        Window window = getWindow();
        WindowCompat.setDecorFitsSystemWindows(window, true);

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
            window.setStatusBarColor(Color.rgb(248, 250, 252));
            window.setNavigationBarColor(Color.rgb(17, 24, 39));
        }

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            window.getDecorView().setSystemUiVisibility(android.view.View.SYSTEM_UI_FLAG_LIGHT_STATUS_BAR);
        }
    }

    private static final class CrmWebViewClient extends BridgeWebViewClient {
        CrmWebViewClient(Bridge bridge) {
            super(bridge);
        }

        @Override
        public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
            Uri url = request.getUrl();

            if (request.isForMainFrame() && normalizeCrmAppUrl(view, url)) {
                return true;
            }

            return super.shouldOverrideUrlLoading(view, request);
        }

        @Override
        public void onPageStarted(WebView view, String url, Bitmap favicon) {
            if (url == null) {
                super.onPageStarted(view, url, favicon);
                return;
            }

            Uri uri = Uri.parse(url);

            if (normalizeCrmAppUrl(view, uri)) {
                return;
            }

            super.onPageStarted(view, url, favicon);
        }

        private boolean normalizeCrmAppUrl(WebView view, Uri uri) {
            String host = uri.getHost();

            if (host == null || (!"crm.jp2.fr".equals(host) && !host.endsWith(".jp2.fr") && !"martinsols.addvancesolutions.fr".equals(host))) {
                return false;
            }

            if (!"1".equals(uri.getQueryParameter("mobile_embed")) || "1".equals(uri.getQueryParameter("mobile_app"))) {
                return false;
            }

            Uri.Builder builder = uri.buildUpon().clearQuery();

            for (String name : uri.getQueryParameterNames()) {
                if ("mobile_embed".equals(name)) {
                    continue;
                }

                for (String value : uri.getQueryParameters(name)) {
                    builder.appendQueryParameter(name, value);
                }
            }

            builder.appendQueryParameter("mobile_app", "1");
            view.loadUrl(builder.build().toString());

            return true;
        }
    }
}
