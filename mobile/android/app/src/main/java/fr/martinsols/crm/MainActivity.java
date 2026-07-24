package fr.martinsols.crm;

import android.app.Activity;
import android.app.AlertDialog;
import android.app.DownloadManager;
import android.content.ActivityNotFoundException;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.DialogInterface;
import android.content.Intent;
import android.content.IntentFilter;
import android.content.res.AssetFileDescriptor;
import android.database.Cursor;
import android.graphics.Color;
import android.graphics.SurfaceTexture;
import android.media.MediaPlayer;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.os.Environment;
import android.os.Handler;
import android.os.Looper;
import android.provider.Settings;
import android.view.Gravity;
import android.view.MotionEvent;
import android.view.Surface;
import android.view.TextureView;
import android.view.View;
import android.view.Window;
import android.view.WindowManager;
import android.webkit.CookieManager;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.FrameLayout;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.TextView;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import org.json.JSONException;
import org.json.JSONObject;

public class MainActivity extends Activity {
    private static final String CRM_URL = "https://crm.jp2.fr/?source=pwa";
    private static final String UPDATE_MANIFEST_URL = "https://raw.githubusercontent.com/jp2creation/crm/main/mobile/releases/martin-sols-update.json";
    private static final String APK_MIME_TYPE = "application/vnd.android.package-archive";
    private static final long SPLASH_DURATION_MS = 5500L;
    private static final long UPDATE_CHECK_DELAY_MS = 1500L;
    private static final long UPDATE_PROGRESS_INTERVAL_MS = 450L;
    private static final int SPLASH_VIDEO_RESOURCE = R.raw.intro;
    private static final int SPLASH_VIDEO_WIDTH = 720;
    private static final int SPLASH_VIDEO_HEIGHT = 1280;
    private static final int UPDATE_PROGRESS_MAX = 100;
    private static final float INTRO_VIDEO_WIDTH_FRACTION = 0.62f;
    private static final float INTRO_VIDEO_HEIGHT_FRACTION = 0.62f;
    private static final int INTRO_VIDEO_MAX_WIDTH_DP = 260;
    private static final int MARTIN_SOLS_RED = Color.rgb(149, 0, 46);
    private static final int MARTIN_SOLS_BACKGROUND = Color.rgb(245, 247, 251);
    private static final int MARTIN_SOLS_NAVIGATION = Color.rgb(17, 24, 39);
    private static final int SPLASH_BACKGROUND = Color.rgb(255, 250, 247);

    private final Handler handler = new Handler(Looper.getMainLooper());
    private FrameLayout rootView;
    private WebView webView;
    private FrameLayout splashLayer;
    private TextureView splashView;
    private Surface splashSurface;
    private MediaPlayer splashPlayer;
    private AppUpdate pendingInstallPermissionUpdate;
    private BroadcastReceiver updateDownloadReceiver;
    private AlertDialog updateProgressDialog;
    private ProgressBar updateProgressBar;
    private TextView updateProgressMessage;
    private TextView updateProgressPercent;
    private long updateDownloadId = -1L;
    private String pendingUpdateSha256 = "";
    private boolean updateCheckStarted;
    private boolean updateInstallStarted;

    private final Runnable hideSplash = new Runnable() {
        @Override
        public void run() {
            hideSplashView();
        }
    };

    private final Runnable updateDownloadProgress = new Runnable() {
        @Override
        public void run() {
            pollUpdateDownloadProgress();
        }
    };

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        requestWindowFeature(Window.FEATURE_NO_TITLE);
        configureSystemBars();
        super.onCreate(savedInstanceState);
        getWindow().setSoftInputMode(WindowManager.LayoutParams.SOFT_INPUT_ADJUST_RESIZE);

        rootView = new FrameLayout(this);

        webView = new WebView(this);
        configureCrmWebView(webView);
        rootView.addView(webView, matchParentLayoutParams());

        splashLayer = new FrameLayout(this);
        splashLayer.setBackgroundColor(SPLASH_BACKGROUND);
        splashView = new IntroTextureView(this);
        configureSplashTextureView(splashView);
        splashLayer.addView(splashView, centeredMatchParentLayoutParams());
        rootView.addView(splashLayer, matchParentLayoutParams());

        setContentView(rootView);
        webView.loadUrl(CRM_URL);
        handler.postDelayed(hideSplash, SPLASH_DURATION_MS);
    }

    @Override
    protected void onResume() {
        super.onResume();
        configureSystemBars();

        if (webView != null) {
            webView.onResume();
        }

        if (pendingInstallPermissionUpdate != null) {
            if (canRequestPackageInstalls()) {
                AppUpdate update = pendingInstallPermissionUpdate;
                pendingInstallPermissionUpdate = null;
                startUpdateDownload(update);
            } else if (!isFinishing()) {
                pendingInstallPermissionUpdate = null;
                showUpdateFailure("Autorisation non accordee : Android bloque l'installation de la mise a jour.");
            }
        }
    }

    @Override
    protected void onPause() {
        if (webView != null) {
            webView.onPause();
        }

        if (splashPlayer != null && splashPlayer.isPlaying()) {
            splashPlayer.pause();
        }

        super.onPause();
    }

    @Override
    public void onBackPressed() {
        if (webView != null && webView.canGoBack()) {
            webView.goBack();
            return;
        }

        super.onBackPressed();
    }

    @Override
    protected void onDestroy() {
        handler.removeCallbacks(hideSplash);
        handler.removeCallbacks(updateDownloadProgress);
        unregisterUpdateDownloadReceiver();
        dismissUpdateProgressDialog();
        releaseSplashPlayer();
        releaseSplashSurface();
        splashView = null;
        splashLayer = null;
        rootView = null;
        destroyWebView(webView);
        webView = null;
        super.onDestroy();
    }

    private FrameLayout.LayoutParams matchParentLayoutParams() {
        return new FrameLayout.LayoutParams(
            FrameLayout.LayoutParams.MATCH_PARENT,
            FrameLayout.LayoutParams.MATCH_PARENT
        );
    }

    private FrameLayout.LayoutParams centeredMatchParentLayoutParams() {
        FrameLayout.LayoutParams params = matchParentLayoutParams();
        params.gravity = Gravity.CENTER;

        return params;
    }

    private void configureCrmWebView(WebView view) {
        view.setBackgroundColor(MARTIN_SOLS_BACKGROUND);
        view.setOverScrollMode(View.OVER_SCROLL_NEVER);
        view.setWebViewClient(new CrmWebViewClient());
        view.setWebChromeClient(new WebChromeClient());
        view.setVerticalScrollBarEnabled(false);
        view.setHorizontalScrollBarEnabled(false);

        WebSettings settings = view.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setDatabaseEnabled(true);
        settings.setGeolocationEnabled(false);
        settings.setJavaScriptCanOpenWindowsAutomatically(true);
        settings.setMediaPlaybackRequiresUserGesture(false);

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            settings.setSafeBrowsingEnabled(true);
        }

        CookieManager cookieManager = CookieManager.getInstance();
        cookieManager.setAcceptCookie(true);

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
            cookieManager.setAcceptThirdPartyCookies(view, true);
        }
    }

    private void configureSplashTextureView(TextureView view) {
        view.setOverScrollMode(View.OVER_SCROLL_NEVER);
        view.setOnTouchListener(new View.OnTouchListener() {
            @Override
            public boolean onTouch(View touchedView, MotionEvent event) {
                return true;
            }
        });
        view.setSurfaceTextureListener(new TextureView.SurfaceTextureListener() {
            @Override
            public void onSurfaceTextureAvailable(SurfaceTexture surfaceTexture, int width, int height) {
                prepareSplashPlayer(surfaceTexture);
            }

            @Override
            public void onSurfaceTextureSizeChanged(SurfaceTexture surfaceTexture, int width, int height) {
            }

            @Override
            public boolean onSurfaceTextureDestroyed(SurfaceTexture surfaceTexture) {
                releaseSplashPlayer();
                releaseSplashSurface();

                return true;
            }

            @Override
            public void onSurfaceTextureUpdated(SurfaceTexture surfaceTexture) {
            }
        });
    }

    private void configureSystemBars() {
        Window window = getWindow();

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
            window.setDecorFitsSystemWindows(true);
        }

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
            window.setStatusBarColor(MARTIN_SOLS_RED);
            window.setNavigationBarColor(MARTIN_SOLS_NAVIGATION);
        }

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            window.getDecorView().setSystemUiVisibility(0);
        }
    }

    private void hideSplashView() {
        FrameLayout layer = splashLayer;

        if (layer == null) {
            return;
        }

        splashLayer = null;
        splashView = null;
        layer.removeAllViews();
        layer.setVisibility(View.GONE);

        if (rootView != null) {
            rootView.removeView(layer);
        }

        releaseSplashPlayer();
        releaseSplashSurface();
        showCrmWebView();
    }

    private void showCrmWebView() {
        if (webView == null) {
            return;
        }

        webView.setVisibility(View.VISIBLE);
        webView.bringToFront();
        webView.resumeTimers();
        webView.invalidate();

        String currentUrl = webView.getUrl();

        if (currentUrl == null || currentUrl.length() == 0 || "about:blank".equalsIgnoreCase(currentUrl)) {
            webView.loadUrl(CRM_URL);
        }

        scheduleUpdateCheck();
    }

    private void scheduleUpdateCheck() {
        if (updateCheckStarted) {
            return;
        }

        updateCheckStarted = true;
        handler.postDelayed(new Runnable() {
            @Override
            public void run() {
                checkForAppUpdate();
            }
        }, UPDATE_CHECK_DELAY_MS);
    }

    private void checkForAppUpdate() {
        new Thread(new Runnable() {
            @Override
            public void run() {
                AppUpdate update = fetchAppUpdate();

                if (update == null || update.versionCode <= BuildConfig.VERSION_CODE) {
                    return;
                }

                handler.post(new Runnable() {
                    @Override
                    public void run() {
                        showUpdateDialog(update);
                    }
                });
            }
        }).start();
    }

    private AppUpdate fetchAppUpdate() {
        HttpURLConnection connection = null;

        try {
            URL url = new URL(UPDATE_MANIFEST_URL + "?t=" + System.currentTimeMillis());
            connection = (HttpURLConnection) url.openConnection();
            connection.setConnectTimeout(5000);
            connection.setReadTimeout(5000);
            connection.setRequestMethod("GET");
            connection.setRequestProperty("Accept", "application/json");

            int responseCode = connection.getResponseCode();

            if (responseCode < 200 || responseCode >= 300) {
                return null;
            }

            JSONObject manifest = new JSONObject(readText(connection.getInputStream()));
            JSONObject android = manifest.optJSONObject("android");

            if (android == null) {
                return null;
            }

            int versionCode = android.optInt("versionCode", 0);
            String versionName = android.optString("versionName", "");
            String apkUrl = android.optString("apkUrl", "");
            String sha256 = android.optString("sha256", "");
            String releaseNotes = android.optString("releaseNotes", "");

            if (versionCode <= 0 || apkUrl.length() == 0) {
                return null;
            }

            return new AppUpdate(versionCode, versionName, apkUrl, sha256, releaseNotes);
        } catch (IOException | JSONException exception) {
            return null;
        } finally {
            if (connection != null) {
                connection.disconnect();
            }
        }
    }

    private void showUpdateDialog(AppUpdate update) {
        if (isFinishing()) {
            return;
        }

        String versionLabel = update.versionName.length() > 0 ? update.versionName : String.valueOf(update.versionCode);
        String message = "Une nouvelle version de Martin Sols est disponible : " + versionLabel + ".";

        if (update.releaseNotes.length() > 0) {
            message = message + "\n\n" + update.releaseNotes;
        }

        new AlertDialog.Builder(this)
            .setTitle("Mise a jour disponible")
            .setMessage(message)
            .setPositiveButton("Installer", new DialogInterface.OnClickListener() {
                @Override
                public void onClick(DialogInterface dialog, int which) {
                    startUpdateDownload(update);
                }
            })
            .setNegativeButton("Plus tard", new DialogInterface.OnClickListener() {
                @Override
                public void onClick(DialogInterface dialog, int which) {
                    pendingInstallPermissionUpdate = null;
                }
            })
            .show();
    }

    private void startUpdateDownload(AppUpdate update) {
        if (!canRequestPackageInstalls()) {
            pendingInstallPermissionUpdate = update;
            showInstallPermissionDialog(update);

            return;
        }

        DownloadManager downloadManager = (DownloadManager) getSystemService(Context.DOWNLOAD_SERVICE);

        if (downloadManager == null) {
            showUpdateFailure("Android ne peut pas lancer le telechargement.");

            return;
        }

        cancelActiveUpdateDownload(false);
        pendingUpdateSha256 = update.sha256;
        updateInstallStarted = false;
        String versionLabel = update.versionName.length() > 0 ? update.versionName : String.valueOf(update.versionCode);
        String fileName = "Martin_Sols_" + versionLabel + ".apk";
        DownloadManager.Request request = new DownloadManager.Request(Uri.parse(update.apkUrl));
        request.setTitle("Mise a jour Martin Sols");
        request.setDescription("Telechargement de la version " + versionLabel);
        request.setMimeType(APK_MIME_TYPE);
        request.setAllowedOverMetered(true);
        request.setAllowedOverRoaming(true);
        request.setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED);
        request.setDestinationInExternalFilesDir(this, Environment.DIRECTORY_DOWNLOADS, fileName);

        updateDownloadId = downloadManager.enqueue(request);
        registerUpdateDownloadReceiver();
        showUpdateProgressDialog(update);
        handler.removeCallbacks(updateDownloadProgress);
        handler.post(updateDownloadProgress);
    }

    private boolean canRequestPackageInstalls() {
        return Build.VERSION.SDK_INT < Build.VERSION_CODES.O || getPackageManager().canRequestPackageInstalls();
    }

    private void showInstallPermissionDialog(AppUpdate update) {
        if (isFinishing()) {
            return;
        }

        new AlertDialog.Builder(this)
            .setTitle("Autorisation Android requise")
            .setMessage("Pour installer la mise a jour, autorise Martin Sols a installer des apps inconnues. Reviens ensuite dans l'app : le telechargement demarrera automatiquement.")
            .setPositiveButton("Ouvrir les reglages", new DialogInterface.OnClickListener() {
                @Override
                public void onClick(DialogInterface dialog, int which) {
                    openInstallPermissionSettings(update);
                }
            })
            .setNegativeButton("Plus tard", new DialogInterface.OnClickListener() {
                @Override
                public void onClick(DialogInterface dialog, int which) {
                    pendingInstallPermissionUpdate = null;
                }
            })
            .show();
    }

    private void openInstallPermissionSettings(AppUpdate update) {
        pendingInstallPermissionUpdate = update;

        try {
            Intent intent = new Intent(Settings.ACTION_MANAGE_UNKNOWN_APP_SOURCES);
            intent.setData(Uri.parse("package:" + getPackageName()));
            startActivity(intent);
        } catch (ActivityNotFoundException exception) {
            Intent fallbackIntent = new Intent(Settings.ACTION_SECURITY_SETTINGS);
            startActivity(fallbackIntent);
        }
    }

    private void registerUpdateDownloadReceiver() {
        unregisterUpdateDownloadReceiver();
        updateDownloadReceiver = new BroadcastReceiver() {
            @Override
            public void onReceive(Context context, Intent intent) {
                long completedDownloadId = intent.getLongExtra(DownloadManager.EXTRA_DOWNLOAD_ID, -1L);

                if (completedDownloadId != updateDownloadId) {
                    return;
                }

                installDownloadedUpdate();
            }
        };

        IntentFilter filter = new IntentFilter(DownloadManager.ACTION_DOWNLOAD_COMPLETE);

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            registerReceiver(updateDownloadReceiver, filter, Context.RECEIVER_NOT_EXPORTED);
            return;
        }

        registerReceiver(updateDownloadReceiver, filter);
    }

    private void unregisterUpdateDownloadReceiver() {
        if (updateDownloadReceiver == null) {
            return;
        }

        unregisterReceiver(updateDownloadReceiver);
        updateDownloadReceiver = null;
    }

    private void showUpdateProgressDialog(AppUpdate update) {
        if (isFinishing()) {
            return;
        }

        dismissUpdateProgressDialog();

        String versionLabel = update.versionName.length() > 0 ? update.versionName : String.valueOf(update.versionCode);
        LinearLayout layout = new LinearLayout(this);
        layout.setOrientation(LinearLayout.VERTICAL);
        layout.setPadding(dp(24), dp(12), dp(24), 0);

        updateProgressMessage = new TextView(this);
        updateProgressMessage.setText("Preparation du telechargement de la version " + versionLabel + "...");
        updateProgressMessage.setTextColor(Color.rgb(31, 53, 79));
        updateProgressMessage.setTextSize(15);
        layout.addView(updateProgressMessage, new LinearLayout.LayoutParams(
            LinearLayout.LayoutParams.MATCH_PARENT,
            LinearLayout.LayoutParams.WRAP_CONTENT
        ));

        updateProgressBar = new ProgressBar(this, null, android.R.attr.progressBarStyleHorizontal);
        updateProgressBar.setMax(UPDATE_PROGRESS_MAX);
        updateProgressBar.setIndeterminate(true);
        LinearLayout.LayoutParams progressParams = new LinearLayout.LayoutParams(
            LinearLayout.LayoutParams.MATCH_PARENT,
            LinearLayout.LayoutParams.WRAP_CONTENT
        );
        progressParams.setMargins(0, dp(16), 0, dp(8));
        layout.addView(updateProgressBar, progressParams);

        updateProgressPercent = new TextView(this);
        updateProgressPercent.setText("En attente");
        updateProgressPercent.setTextColor(Color.rgb(100, 116, 139));
        updateProgressPercent.setTextSize(13);
        updateProgressPercent.setGravity(Gravity.RIGHT);
        layout.addView(updateProgressPercent, new LinearLayout.LayoutParams(
            LinearLayout.LayoutParams.MATCH_PARENT,
            LinearLayout.LayoutParams.WRAP_CONTENT
        ));

        updateProgressDialog = new AlertDialog.Builder(this)
            .setTitle("Mise a jour Martin Sols")
            .setView(layout)
            .setNegativeButton("Annuler", new DialogInterface.OnClickListener() {
                @Override
                public void onClick(DialogInterface dialog, int which) {
                    cancelActiveUpdateDownload(true);
                }
            })
            .create();
        updateProgressDialog.setCanceledOnTouchOutside(false);
        updateProgressDialog.show();
    }

    private void pollUpdateDownloadProgress() {
        if (updateDownloadId < 0 || updateInstallStarted) {
            return;
        }

        DownloadManager downloadManager = (DownloadManager) getSystemService(Context.DOWNLOAD_SERVICE);

        if (downloadManager == null) {
            showUpdateFailure("Android ne peut pas lire la progression du telechargement.");

            return;
        }

        DownloadManager.Query query = new DownloadManager.Query();
        query.setFilterById(updateDownloadId);

        try (Cursor cursor = downloadManager.query(query)) {
            if (cursor == null || !cursor.moveToFirst()) {
                showUpdateFailure("Telechargement introuvable.");

                return;
            }

            int status = intColumn(cursor, DownloadManager.COLUMN_STATUS);
            int reason = intColumn(cursor, DownloadManager.COLUMN_REASON);
            long downloadedBytes = longColumn(cursor, DownloadManager.COLUMN_BYTES_DOWNLOADED_SO_FAR);
            long totalBytes = longColumn(cursor, DownloadManager.COLUMN_TOTAL_SIZE_BYTES);
            int progress = progressPercent(downloadedBytes, totalBytes);

            if (status == DownloadManager.STATUS_SUCCESSFUL) {
                updateProgressUi("Telechargement termine. Verification du fichier...", UPDATE_PROGRESS_MAX, false);
                installDownloadedUpdate();

                return;
            }

            if (status == DownloadManager.STATUS_FAILED) {
                showUpdateFailure("Telechargement impossible. Code Android : " + reason + ".");

                return;
            }

            if (status == DownloadManager.STATUS_PAUSED) {
                updateProgressUi("Telechargement en pause. Android reprendra automatiquement. Code : " + reason + ".", progress, totalBytes <= 0);
            } else if (status == DownloadManager.STATUS_PENDING) {
                updateProgressUi("Demarrage du telechargement...", progress, true);
            } else {
                updateProgressUi("Telechargement en cours...", progress, totalBytes <= 0);
            }

            handler.postDelayed(updateDownloadProgress, UPDATE_PROGRESS_INTERVAL_MS);
        } catch (RuntimeException exception) {
            showUpdateFailure("Android ne peut pas suivre le telechargement.");
        }
    }

    private void updateProgressUi(String message, int progress, boolean indeterminate) {
        if (updateProgressMessage != null) {
            updateProgressMessage.setText(message);
        }

        if (updateProgressBar != null) {
            updateProgressBar.setIndeterminate(indeterminate);
            updateProgressBar.setProgress(Math.max(0, Math.min(UPDATE_PROGRESS_MAX, progress)));
        }

        if (updateProgressPercent != null) {
            updateProgressPercent.setText(indeterminate ? "En cours..." : progress + " %");
        }
    }

    private void showUpdateFailure(String message) {
        handler.removeCallbacks(updateDownloadProgress);
        unregisterUpdateDownloadReceiver();
        cancelActiveUpdateDownload(false);
        dismissUpdateProgressDialog();

        if (isFinishing()) {
            return;
        }

        new AlertDialog.Builder(this)
            .setTitle("Mise a jour interrompue")
            .setMessage(message)
            .setPositiveButton("OK", null)
            .show();
    }

    private void cancelActiveUpdateDownload(boolean showMessage) {
        handler.removeCallbacks(updateDownloadProgress);

        if (updateDownloadId >= 0) {
            DownloadManager downloadManager = (DownloadManager) getSystemService(Context.DOWNLOAD_SERVICE);

            if (downloadManager != null) {
                downloadManager.remove(updateDownloadId);
            }
        }

        updateDownloadId = -1L;
        pendingUpdateSha256 = "";
        updateInstallStarted = false;

        if (showMessage && !isFinishing()) {
            dismissUpdateProgressDialog();
            new AlertDialog.Builder(this)
                .setTitle("Mise a jour annulee")
                .setMessage("Le telechargement a ete annule.")
                .setPositiveButton("OK", null)
                .show();
        }
    }

    private void dismissUpdateProgressDialog() {
        if (updateProgressDialog != null) {
            updateProgressDialog.dismiss();
            updateProgressDialog = null;
        }

        updateProgressBar = null;
        updateProgressMessage = null;
        updateProgressPercent = null;
    }

    private void installDownloadedUpdate() {
        if (updateInstallStarted) {
            return;
        }

        DownloadManager downloadManager = (DownloadManager) getSystemService(Context.DOWNLOAD_SERVICE);

        if (downloadManager == null || updateDownloadId < 0) {
            showUpdateFailure("Android ne trouve pas le fichier telecharge.");

            return;
        }

        Uri apkUri = downloadManager.getUriForDownloadedFile(updateDownloadId);

        if (apkUri == null || !isDownloadedUpdateValid(apkUri)) {
            showUpdateFailure("Controle de securite impossible : le fichier telecharge ne correspond pas a la version attendue.");

            return;
        }

        updateInstallStarted = true;
        handler.removeCallbacks(updateDownloadProgress);
        unregisterUpdateDownloadReceiver();
        updateProgressUi("Ouverture de l'installateur Android...", UPDATE_PROGRESS_MAX, false);

        Intent installIntent = new Intent(Intent.ACTION_VIEW);
        installIntent.setDataAndType(apkUri, APK_MIME_TYPE);
        installIntent.addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION);
        installIntent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);

        try {
            startActivity(installIntent);
            handler.postDelayed(new Runnable() {
                @Override
                public void run() {
                    dismissUpdateProgressDialog();
                }
            }, 1200L);
        } catch (ActivityNotFoundException exception) {
            showUpdateFailure("Android n'a pas pu ouvrir l'installateur.");
        }
    }

    private boolean isDownloadedUpdateValid(Uri apkUri) {
        if (pendingUpdateSha256 == null || pendingUpdateSha256.length() == 0) {
            return true;
        }

        String downloadedSha256 = sha256(apkUri);

        return pendingUpdateSha256.equalsIgnoreCase(downloadedSha256);
    }

    private String sha256(Uri uri) {
        try (InputStream inputStream = getContentResolver().openInputStream(uri)) {
            if (inputStream == null) {
                return "";
            }

            MessageDigest digest = MessageDigest.getInstance("SHA-256");
            byte[] buffer = new byte[8192];
            int read;

            while ((read = inputStream.read(buffer)) != -1) {
                digest.update(buffer, 0, read);
            }

            byte[] hash = digest.digest();
            StringBuilder builder = new StringBuilder(hash.length * 2);

            for (byte value : hash) {
                builder.append(String.format("%02x", value));
            }

            return builder.toString();
        } catch (IOException | NoSuchAlgorithmException exception) {
            return "";
        }
    }

    private static String readText(InputStream inputStream) throws IOException {
        try (InputStream stream = inputStream; ByteArrayOutputStream output = new ByteArrayOutputStream()) {
            byte[] buffer = new byte[4096];
            int read;

            while ((read = stream.read(buffer)) != -1) {
                output.write(buffer, 0, read);
            }

            return output.toString("UTF-8");
        }
    }

    private static int progressPercent(long downloadedBytes, long totalBytes) {
        if (downloadedBytes <= 0 || totalBytes <= 0) {
            return 0;
        }

        return Math.min(99, Math.round((downloadedBytes * 100f) / totalBytes));
    }

    private static int intColumn(Cursor cursor, String columnName) {
        int columnIndex = cursor.getColumnIndex(columnName);

        if (columnIndex < 0) {
            return 0;
        }

        return cursor.getInt(columnIndex);
    }

    private static long longColumn(Cursor cursor, String columnName) {
        int columnIndex = cursor.getColumnIndex(columnName);

        if (columnIndex < 0) {
            return 0L;
        }

        return cursor.getLong(columnIndex);
    }

    private int dp(int value) {
        return Math.round(value * getResources().getDisplayMetrics().density);
    }

    private void prepareSplashPlayer(SurfaceTexture surfaceTexture) {
        releaseSplashPlayer();
        releaseSplashSurface();

        splashSurface = new Surface(surfaceTexture);
        splashPlayer = new MediaPlayer();

        try (AssetFileDescriptor descriptor = getResources().openRawResourceFd(SPLASH_VIDEO_RESOURCE)) {
            if (descriptor == null) {
                hideSplashView();

                return;
            }

            splashPlayer.setDataSource(descriptor.getFileDescriptor(), descriptor.getStartOffset(), descriptor.getLength());
            splashPlayer.setSurface(splashSurface);
            splashPlayer.setVolume(0.0f, 0.0f);
            splashPlayer.setLooping(false);
            splashPlayer.setOnPreparedListener(new MediaPlayer.OnPreparedListener() {
                @Override
                public void onPrepared(MediaPlayer mediaPlayer) {
                    mediaPlayer.start();
                }
            });
            splashPlayer.setOnCompletionListener(new MediaPlayer.OnCompletionListener() {
                @Override
                public void onCompletion(MediaPlayer mediaPlayer) {
                    hideSplashView();
                }
            });
            splashPlayer.setOnErrorListener(new MediaPlayer.OnErrorListener() {
                @Override
                public boolean onError(MediaPlayer mediaPlayer, int what, int extra) {
                    hideSplashView();

                    return true;
                }
            });
            splashPlayer.prepareAsync();
        } catch (IOException exception) {
            hideSplashView();
        }
    }

    private void releaseSplashPlayer() {
        if (splashPlayer == null) {
            return;
        }

        splashPlayer.setOnPreparedListener(null);
        splashPlayer.setOnCompletionListener(null);
        splashPlayer.setOnErrorListener(null);
        splashPlayer.release();
        splashPlayer = null;
    }

    private void releaseSplashSurface() {
        if (splashSurface == null) {
            return;
        }

        splashSurface.release();
        splashSurface = null;
    }

    private void destroyWebView(WebView view) {
        if (view == null) {
            return;
        }

        view.stopLoading();
        view.loadUrl("about:blank");
        view.destroy();
    }

    private static final class CrmWebViewClient extends WebViewClient {
        @Override
        public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
            if (isWebUrl(request.getUrl())) {
                return false;
            }

            return true;
        }

        @Override
        public boolean shouldOverrideUrlLoading(WebView view, String url) {
            if (isWebUrl(Uri.parse(url))) {
                return false;
            }

            return true;
        }

        private static boolean isWebUrl(Uri uri) {
            String scheme = uri.getScheme();

            return "http".equalsIgnoreCase(scheme) || "https".equalsIgnoreCase(scheme);
        }
    }

    private static final class IntroTextureView extends TextureView {
        public IntroTextureView(Activity context) {
            super(context);
        }

        @Override
        protected void onMeasure(int widthMeasureSpec, int heightMeasureSpec) {
            int viewWidth = MeasureSpec.getSize(widthMeasureSpec);
            int viewHeight = MeasureSpec.getSize(heightMeasureSpec);
            float videoAspectRatio = (float) SPLASH_VIDEO_WIDTH / (float) SPLASH_VIDEO_HEIGHT;
            float density = getResources().getDisplayMetrics().density;
            int maxWidth = Math.round(INTRO_VIDEO_MAX_WIDTH_DP * density);
            int desiredWidth = Math.min(Math.round(viewWidth * INTRO_VIDEO_WIDTH_FRACTION), maxWidth);
            int desiredHeight = Math.round(desiredWidth / videoAspectRatio);
            int maxHeight = Math.round(viewHeight * INTRO_VIDEO_HEIGHT_FRACTION);

            if (desiredHeight > maxHeight) {
                desiredHeight = maxHeight;
                desiredWidth = Math.round(desiredHeight * videoAspectRatio);
            }

            setMeasuredDimension(desiredWidth, desiredHeight);
        }
    }

    private static final class AppUpdate {
        private final int versionCode;
        private final String versionName;
        private final String apkUrl;
        private final String sha256;
        private final String releaseNotes;

        private AppUpdate(int versionCode, String versionName, String apkUrl, String sha256, String releaseNotes) {
            this.versionCode = versionCode;
            this.versionName = versionName;
            this.apkUrl = apkUrl;
            this.sha256 = sha256;
            this.releaseNotes = releaseNotes;
        }
    }
}
