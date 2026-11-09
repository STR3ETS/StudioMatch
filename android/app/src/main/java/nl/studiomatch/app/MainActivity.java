package nl.studiomatch.app;

import android.os.Bundle;
import android.webkit.WebBackForwardList;
import android.webkit.WebView;
import androidx.activity.OnBackPressedCallback;
import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {

    private StudioMatchWebViewClient webViewClient;

    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        webViewClient = new StudioMatchWebViewClient(bridge);
        bridge.setWebViewClient(webViewClient);

        // Terugknop, ook op een websitepagina (daar is de Capacitor-brug er niet, en zou de terugknop van de
        // App-plugin niets doen). Later toegevoegd dan de callback van de App-plugin, dus deze gaat voor.
        getOnBackPressedDispatcher().addCallback(this, new OnBackPressedCallback(true) {
            @Override
            public void handleOnBackPressed() {
                goBack();
            }
        });
    }

    /**
     * Op een websitepagina: terug binnen hetzelfde bezoek, en vanaf de eerste pagina van dat bezoek naar het
     * scherm in de app waar je vandaan kwam. Daarna gaat de geschiedenis weg, anders belandt de terugknop
     * later in een oude websitepagina (eerst een studio bekijken, dan Facturen, terug: weer de studio).
     *
     * In de app: eerst mag de app zelf iets sluiten of een stap terug doen (window.smBack in main.js, bijv.
     * het venster onderin of van een boeking naar de lijst), dan een scherm terug, en anders de app naar de
     * achtergrond, zodat hij bij terugkomen staat waar hij stond.
     */
    private void goBack() {
        WebView webView = bridge.getWebView();
        String appUrl = bridge.getAppUrl();
        WebBackForwardList history = webView.copyBackForwardList();
        int current = history.getCurrentIndex();
        String previous = current > 0 ? history.getItemAtIndex(current - 1).getUrl() : null;

        if (!isAppScreen(webView.getUrl(), appUrl)) {
            boolean offlinePage = webView.getUrl() != null && webView.getUrl().contains("offline.html");
            if (!offlinePage && previous != null && !isAppScreen(previous, appUrl) && !previous.contains("offline.html")) {
                webView.goBack();
                return;
            }
            String target = appUrl;
            for (int index = current - 1; index >= 0; index--) {
                String url = history.getItemAtIndex(index).getUrl();
                if (isAppScreen(url, appUrl)) {
                    target = url;
                    break;
                }
            }
            webViewClient.clearHistoryAfterNextLoad();
            webView.loadUrl(target);
            return;
        }

        boolean canGoBackInApp = isAppScreen(previous, appUrl);
        webView.evaluateJavascript("window.smBack ? window.smBack(" + canGoBackInApp + ") : false", (handled) -> {
            if ("true".equals(handled)) {
                return;
            }
            if (canGoBackInApp) {
                webView.goBack();
            } else {
                moveTaskToBack(true);
            }
        });
    }

    /** Een scherm van de app zelf (niet een websitepagina, en niet de pagina zonder verbinding). */
    private static boolean isAppScreen(String url, String appUrl) {
        return url != null && appUrl != null && url.startsWith(appUrl) && !url.contains("offline.html");
    }
}
