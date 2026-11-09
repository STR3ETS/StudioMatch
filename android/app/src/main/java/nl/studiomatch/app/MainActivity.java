package nl.studiomatch.app;

import android.os.Bundle;
import android.webkit.WebView;
import androidx.activity.OnBackPressedCallback;
import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {

    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        // Terugknop, ook op een serverpagina (daar is de Capacitor-brug er niet, en zou de terugknop van de
        // App-plugin niets doen). Binnen de app is elk scherm een eigen stap in de geschiedenis (#/...).
        // De eerste serverpagina staat niet in de geschiedenis van de webview (de app zelf laadt Capacitor
        // apart), dus daarvandaan de app opnieuw openen. Niets meer om terug te gaan: de app naar de
        // achtergrond, zodat hij bij terugkomen staat waar hij stond. Later toegevoegd dan de callback van
        // de App-plugin, dus deze gaat voor.
        getOnBackPressedDispatcher().addCallback(this, new OnBackPressedCallback(true) {
            @Override
            public void handleOnBackPressed() {
                WebView webView = getBridge().getWebView();
                String url = webView.getUrl();
                String appUrl = getBridge().getAppUrl();
                if (webView.canGoBack()) {
                    webView.goBack();
                } else if (url != null && appUrl != null && !url.startsWith(appUrl)) {
                    webView.loadUrl(appUrl);
                } else {
                    moveTaskToBack(true);
                }
            }
        });
    }
}
