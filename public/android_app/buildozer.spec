[app]
title = Yuwa Queue Browser
package.name = yuwa_queue_browser
package.domain = go.th.ycap.que
source.dir = .
source.include_exts = py
version = 0.1.0
requirements = python3,kivy,pyjnius
orientation = portrait
fullscreen = 0
android.permissions = INTERNET
android.api = 33
android.minapi = 25
android.archs = armeabi-v7a,arm64-v8a,x86,x86_64
android.sdk_path = $ANDROID_SDK_ROOT
android.ndk_path = $ANDROID_NDK_HOME
android.gradle_dependencies = androidx.print:print:1.0.0
p4a.local_recipes =

[buildozer]
log_level = 2
warn_on_root = 1

[app:android]
# Enable WebView debugging for easier testing (disabled in release builds).
android.add_javacode = src/com/yuwa/browser/WebViewDebug.java

[android]
# Additional java source code (for enabling WebView debugging in debug builds).
# The file referenced in android.add_javacode is generated automatically when
# running buildozer. A template is provided in README instructions.
