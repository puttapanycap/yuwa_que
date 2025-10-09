"""Android WebView application for que.ycap.go.th with printer selector support.

This script is meant to be packaged with python-for-android/Buildozer. It uses the
standard SDL2 bootstrap but replaces the root view hierarchy with native Android
widgets so that we can embed an Android WebView and access the Android printing
framework. The WebView opens https://que.ycap.go.th by default and exposes a
"เลือกเครื่องพิมพ์" (Select Printer) button that allows the user to pick a
printer via Android's PrintManager UI.

The implementation targets Android 7.1.2 (API level 25) and newer.
"""

from kivy.app import App
from kivy.clock import Clock
from kivy.uix.widget import Widget

from android.runnable import run_on_ui_thread
from jnius import PythonJavaClass, autoclass, cast, java_method


class _OnClickListener(PythonJavaClass):
    """Bridge Android's View.OnClickListener to a Python callback."""

    __javainterfaces__ = ["android/view/View$OnClickListener"]
    __javacontext__ = "app"

    def __init__(self, callback):
        super().__init__()
        self._callback = callback

    @java_method("(Landroid/view/View;)V")
    def onClick(self, _view):  # pragma: no cover - executed on JVM side
        if self._callback:
            self._callback()


class _AndroidBrowserController:
    """Encapsulates all interaction with the Android UI toolkits."""

    WEB_URL = "https://que.ycap.go.th"

    def __init__(self):
        self._activity = None
        self._root_layout = None
        self._toolbar_layout = None
        self._webview = None
        self._printer_bridge = None

    def initialise(self):
        self._initialise_ui()

    @run_on_ui_thread
    def _initialise_ui(self):
        PythonActivity = autoclass("org.kivy.android.PythonActivity")
        LinearLayout = autoclass("android.widget.LinearLayout")
        LayoutParams = autoclass("android.widget.LinearLayout$LayoutParams")
        Button = autoclass("android.widget.Button")
        WebView = autoclass("android.webkit.WebView")
        WebViewDebug = autoclass("com.yuwa.browser.WebViewDebug")

        self._activity = PythonActivity.mActivity

        # Enable remote debugging support when running debug builds.
        WebViewDebug.enable()

        # Root container: vertical LinearLayout with toolbar + WebView.
        self._root_layout = LinearLayout(self._activity)
        self._root_layout.setOrientation(LinearLayout.VERTICAL)

        match_parent = LayoutParams(LayoutParams.MATCH_PARENT, LayoutParams.MATCH_PARENT)
        self._activity.setContentView(self._root_layout, match_parent)

        # Toolbar container for action buttons.
        self._toolbar_layout = LinearLayout(self._activity)
        self._toolbar_layout.setOrientation(LinearLayout.HORIZONTAL)

        toolbar_params = LayoutParams(LayoutParams.MATCH_PARENT, LayoutParams.WRAP_CONTENT)
        self._root_layout.addView(self._toolbar_layout, toolbar_params)

        button_params = LayoutParams(0, LayoutParams.WRAP_CONTENT, 1.0)

        reload_button = Button(self._activity)
        reload_button.setText("รีเฟรช")
        reload_button.setOnClickListener(_OnClickListener(self._reload_page))
        self._toolbar_layout.addView(reload_button, button_params)

        print_button = Button(self._activity)
        print_button.setText("เลือกเครื่องพิมพ์")
        print_button.setOnClickListener(_OnClickListener(self._trigger_print_dialog))
        self._toolbar_layout.addView(print_button, button_params)

        # Configure the WebView used as the browser surface.
        self._webview = WebView(self._activity)
        webview_params = LayoutParams(LayoutParams.MATCH_PARENT, 0, 1.0)
        self._root_layout.addView(self._webview, webview_params)

        settings = self._webview.getSettings()
        settings.setJavaScriptEnabled(True)
        settings.setDomStorageEnabled(True)
        settings.setLoadWithOverviewMode(True)
        settings.setUseWideViewPort(True)
        settings.setSupportMultipleWindows(False)
        settings.setBuiltInZoomControls(True)
        settings.setDisplayZoomControls(False)

        AndroidPrinterBridge = autoclass("com.yuwa.browser.AndroidPrinterBridge")
        self._printer_bridge = AndroidPrinterBridge(self._activity, self._webview)
        self._webview.addJavascriptInterface(self._printer_bridge, "AndroidPrinter")

        self._webview.loadUrl(self.WEB_URL)

    @run_on_ui_thread
    def _reload_page(self):
        if self._webview:
            self._webview.reload()

    @run_on_ui_thread
    def _trigger_print_dialog(self):
        if not self._webview or not self._activity:
            return

        PrintManager = autoclass("android.print.PrintManager")
        PrintAttributesBuilder = autoclass("android.print.PrintAttributes$Builder")

        print_manager = cast(
            "android.print.PrintManager",
            self._activity.getSystemService(self._activity.PRINT_SERVICE),
        )

        if not print_manager:
            return

        job_name = "Yuwa Queue Printer"
        adapter = self._webview.createPrintDocumentAdapter(job_name)
        attributes = PrintAttributesBuilder().setColorMode(
            autoclass("android.print.PrintAttributes").COLOR_MODE_COLOR
        ).build()

        # Launch Android's print dialog so the user can pick a printer.
        print_manager.print(job_name, adapter, attributes)

    @run_on_ui_thread
    def cleanup(self):
        if self._printer_bridge:
            self._printer_bridge.cleanup()
            self._printer_bridge = None

        if self._webview:
            self._webview.destroy()
            self._webview = None

        if self._root_layout:
            self._root_layout.removeAllViews()
            self._root_layout = None


class YuwaQueueBrowserApp(App):
    """Kivy application wrapper that keeps the Python runtime alive."""

    def build(self):
        self._controller = _AndroidBrowserController()
        # Defer initialisation until the Kivy window is ready.
        Clock.schedule_once(lambda *_: self._controller.initialise(), 0)
        return Widget()

    def on_stop(self):
        if hasattr(self, "_controller"):
            self._controller.cleanup()


if __name__ == "__main__":
    YuwaQueueBrowserApp().run()
