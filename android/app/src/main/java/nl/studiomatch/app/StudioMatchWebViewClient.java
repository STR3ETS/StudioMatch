package nl.studiomatch.app;

import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebResourceResponse;
import android.webkit.WebView;
import com.getcapacitor.Bridge;
import com.getcapacitor.BridgeWebViewClient;

/**
 * Capacitor toont server.errorPath (offline.html) bij elke HTTP-fout, dus ook bij een 404 of 403 van de
 * website: dan stond er "Geen verbinding" terwijl de verbinding prima was. Hier alleen bij een echte
 * verbindingsfout; een foutpagina van Laravel blijft gewoon staan, met de balk terug naar de app.
 *
 * Daarnaast: na de terugknop van een websitepagina naar de app (MainActivity) de geschiedenis van de
 * webview wissen zodra de app er staat, zodat de terugknop later niet in een oude websitepagina belandt.
 *
 * De WebViewListener-meldingen voor fouten van de basisklasse vallen hiermee weg; geen van de geïnstalleerde
 * plugins gebruikt die (bij een nieuwe plugin even nakijken).
 */
public class StudioMatchWebViewClient extends BridgeWebViewClient {

    private final Bridge bridge;
    private boolean clearHistoryOnLoad = false;

    public StudioMatchWebViewClient(Bridge bridge) {
        super(bridge);
        this.bridge = bridge;
    }

    /** Na de volgende geladen pagina de geschiedenis wissen. */
    public void clearHistoryAfterNextLoad() {
        clearHistoryOnLoad = true;
    }

    @Override
    public void onPageFinished(WebView view, String url) {
        super.onPageFinished(view, url);
        if (clearHistoryOnLoad) {
            clearHistoryOnLoad = false;
            view.clearHistory();
        }
    }

    @Override
    public void onReceivedHttpError(WebView view, WebResourceRequest request, WebResourceResponse errorResponse) {
        // Bewust leeg: de foutpagina van de website zelf tonen.
    }

    @Override
    public void onReceivedError(WebView view, WebResourceRequest request, WebResourceError error) {
        String errorPath = bridge.getErrorUrl();
        if (errorPath == null || !request.isForMainFrame() || isInterruption(error)) {
            return;
        }
        view.loadUrl(errorPath);
    }

    /** Een navigatie die zelf werd afgebroken (bijv. een nieuwe tik) is geen verbindingsfout. */
    private static boolean isInterruption(WebResourceError error) {
        CharSequence description = error.getDescription();
        return description != null && description.toString().contains("ERR_ABORTED");
    }
}
