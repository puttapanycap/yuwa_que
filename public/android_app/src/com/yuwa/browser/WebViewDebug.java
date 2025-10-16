package com.yuwa.browser;

import android.os.Build;
import android.webkit.WebView;

public class WebViewDebug {
    public static void enable() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.KITKAT) {
            WebView.setWebContentsDebuggingEnabled(true);
        }
    }
}
