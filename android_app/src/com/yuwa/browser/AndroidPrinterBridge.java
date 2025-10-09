package com.yuwa.browser;

import android.app.Activity;
import android.content.Context;
import android.os.Build;
import android.print.PrintAttributes;
import android.print.PrintDocumentAdapter;
import android.print.PrintManager;
import android.util.Base64;
import android.view.View;
import android.view.ViewGroup;
import android.webkit.JavascriptInterface;
import android.webkit.WebView;
import android.webkit.WebViewClient;

import java.nio.charset.StandardCharsets;
import java.util.ArrayList;
import java.util.List;

/**
 * JavaScript bridge that allows the web application to trigger Android's native
 * printing framework. The bridge exposes two entry points:
 *
 * - printCurrentPage(jobName): prints the content currently rendered in the
 *   host WebView.
 * - printHtml(jobName, base64Html): prints arbitrary HTML by rendering it in an
 *   off-screen WebView before delegating to PrintManager.
 */
public class AndroidPrinterBridge {
    private static final String DEFAULT_JOB_NAME = "Yuwa Queue Ticket";

    private final Activity activity;
    private final WebView hostWebView;
    private final List<WebView> activePrintWebViews = new ArrayList<>();

    public AndroidPrinterBridge(Activity activity, WebView hostWebView) {
        this.activity = activity;
        this.hostWebView = hostWebView;
    }

    @JavascriptInterface
    public boolean isSupported() {
        return true;
    }

    @JavascriptInterface
    public void printCurrentPage(String jobName) {
        if (hostWebView == null) {
            return;
        }

        runOnUiThread(() -> {
            PrintManager manager = (PrintManager) activity.getSystemService(Context.PRINT_SERVICE);
            if (manager == null) {
                return;
            }

            String safeJobName = normaliseJobName(jobName);
            PrintDocumentAdapter adapter;
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
                adapter = hostWebView.createPrintDocumentAdapter(safeJobName);
            } else {
                adapter = hostWebView.createPrintDocumentAdapter();
            }

            PrintAttributes attributes = new PrintAttributes.Builder()
                    .setColorMode(PrintAttributes.COLOR_MODE_COLOR)
                    .build();

            manager.print(safeJobName, adapter, attributes);
        });
    }

    @JavascriptInterface
    public void printHtml(String jobName, String base64Html) {
        if (base64Html == null) {
            return;
        }

        final String decodedHtml;
        try {
            byte[] data = Base64.decode(base64Html, Base64.DEFAULT);
            decodedHtml = new String(data, StandardCharsets.UTF_8);
        } catch (IllegalArgumentException ex) {
            return;
        }

        runOnUiThread(() -> {
            final WebView tempWebView = new WebView(activity);
            activePrintWebViews.add(tempWebView);

            tempWebView.getSettings().setJavaScriptEnabled(true);
            tempWebView.getSettings().setDomStorageEnabled(true);
            tempWebView.setVisibility(View.GONE);

            ViewGroup parent = (ViewGroup) hostWebView.getParent();
            if (parent != null) {
                parent.addView(tempWebView, new ViewGroup.LayoutParams(
                        ViewGroup.LayoutParams.MATCH_PARENT,
                        ViewGroup.LayoutParams.WRAP_CONTENT
                ));
            }

            tempWebView.setWebViewClient(new WebViewClient() {
                @Override
                public void onPageFinished(WebView view, String url) {
                    PrintManager manager = (PrintManager) activity.getSystemService(Context.PRINT_SERVICE);
                    if (manager == null) {
                        cleanupTemporaryWebView(view);
                        return;
                    }

                    String safeJobName = normaliseJobName(jobName);
                    PrintDocumentAdapter adapter;
                    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
                        adapter = view.createPrintDocumentAdapter(safeJobName);
                    } else {
                        adapter = view.createPrintDocumentAdapter();
                    }

                    PrintAttributes attributes = new PrintAttributes.Builder()
                            .setColorMode(PrintAttributes.COLOR_MODE_COLOR)
                            .build();

                    manager.print(safeJobName, adapter, attributes);

                    view.postDelayed(() -> cleanupTemporaryWebView(view), 2000);
                }
            });

            tempWebView.loadDataWithBaseURL(null, decodedHtml, "text/html", "UTF-8", null);
        });
    }

    public void cleanup() {
        runOnUiThread(() -> {
            for (WebView webView : new ArrayList<>(activePrintWebViews)) {
                cleanupTemporaryWebView(webView);
            }
            activePrintWebViews.clear();
        });
    }

    private void cleanupTemporaryWebView(WebView webView) {
        if (webView == null) {
            return;
        }

        ViewGroup parent = (ViewGroup) webView.getParent();
        if (parent != null) {
            parent.removeView(webView);
        }

        webView.destroy();
        activePrintWebViews.remove(webView);
    }

    private void runOnUiThread(Runnable runnable) {
        activity.runOnUiThread(runnable);
    }

    private String normaliseJobName(String jobName) {
        if (jobName == null || jobName.trim().isEmpty()) {
            return DEFAULT_JOB_NAME;
        }
        return jobName;
    }
}
