import Capacitor
import UIKit
import WebKit

@objc(MartinSolsBridgeViewController)
class MartinSolsBridgeViewController: CAPBridgeViewController {
    override var preferredStatusBarStyle: UIStatusBarStyle {
        return .lightContent
    }

    override func webViewConfiguration(for instanceConfiguration: InstanceConfiguration) -> WKWebViewConfiguration {
        let configuration = super.webViewConfiguration(for: instanceConfiguration)
        configuration.preferences.javaScriptCanOpenWindowsAutomatically = true
        configuration.allowsInlineMediaPlayback = true
        configuration.mediaTypesRequiringUserActionForPlayback = []

        if #available(iOS 14.0, *) {
            configuration.defaultWebpagePreferences.allowsContentJavaScript = true
        }

        return configuration
    }

    override func webView(with frame: CGRect, configuration: WKWebViewConfiguration) -> WKWebView {
        let crmWebView = super.webView(with: frame, configuration: configuration)
        crmWebView.backgroundColor = UIColor(red: 245.0 / 255.0, green: 247.0 / 255.0, blue: 251.0 / 255.0, alpha: 1)
        crmWebView.allowsBackForwardNavigationGestures = true
        crmWebView.scrollView.keyboardDismissMode = .interactive
        crmWebView.scrollView.contentInsetAdjustmentBehavior = .automatic
        return crmWebView
    }
}
