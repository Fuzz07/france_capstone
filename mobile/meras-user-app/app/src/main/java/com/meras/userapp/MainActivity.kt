package com.meras.userapp

import android.annotation.SuppressLint
import android.app.Activity
import android.content.Intent
import android.graphics.Color
import android.net.Uri
import android.os.Bundle
import android.view.Gravity
import android.view.View
import android.view.ViewGroup
import android.webkit.WebResourceError
import android.webkit.WebResourceRequest
import android.webkit.WebSettings
import android.webkit.WebView
import android.webkit.WebViewClient
import android.widget.Button
import android.widget.FrameLayout
import android.widget.LinearLayout
import android.widget.TextView
import android.widget.Toast

class MainActivity : Activity() {
    private lateinit var webView: WebView
    private lateinit var offlineView: View
    private val customerUrl: String by lazy { getString(R.string.customer_url) }

    @SuppressLint("SetJavaScriptEnabled")
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        try {
            val root = FrameLayout(this)
            webView = WebView(this)
            offlineView = createOfflineView()

            root.addView(webView, FrameLayout.LayoutParams(
                ViewGroup.LayoutParams.MATCH_PARENT,
                ViewGroup.LayoutParams.MATCH_PARENT
            ))
            root.addView(offlineView, FrameLayout.LayoutParams(
                ViewGroup.LayoutParams.MATCH_PARENT,
                ViewGroup.LayoutParams.MATCH_PARENT
            ))
            setContentView(root)

            webView.settings.apply {
                javaScriptEnabled = true
                domStorageEnabled = true
                databaseEnabled = true
                loadsImagesAutomatically = true
                cacheMode = WebSettings.LOAD_DEFAULT
                mixedContentMode = WebSettings.MIXED_CONTENT_COMPATIBILITY_MODE
                setSupportZoom(false)
            }

            webView.webViewClient = object : WebViewClient() {
                override fun shouldOverrideUrlLoading(view: WebView, request: WebResourceRequest): Boolean {
                    return handleNavigation(request.url)
                }

                override fun onPageFinished(view: WebView, url: String) {
                    offlineView.visibility = View.GONE
                    hideStaffControls(view)
                }

                override fun onReceivedError(
                    view: WebView,
                    request: WebResourceRequest,
                    error: WebResourceError
                ) {
                    if (request.isForMainFrame) {
                        offlineView.visibility = View.VISIBLE
                    }
                }
            }

            if (savedInstanceState == null) {
                webView.loadUrl(customerUrl)
            } else {
                webView.restoreState(savedInstanceState)
            }
        } catch (t: Throwable) {
            // Show a user-facing message and log the stacktrace so we can debug the crash
            Toast.makeText(this, "Startup error: ${t.message}", Toast.LENGTH_LONG).show()
            t.printStackTrace()
        }
    }

    private fun handleNavigation(uri: Uri): Boolean {
        val scheme = uri.scheme.orEmpty()
        if (scheme == "mailto" || scheme == "tel") {
            startActivity(Intent(Intent.ACTION_VIEW, uri))
            return true
        }

        if (uri.path.orEmpty().startsWith("/login")) {
            Toast.makeText(this, R.string.staff_portal_blocked, Toast.LENGTH_SHORT).show()
            webView.loadUrl(customerUrl)
            return true
        }

        return false
    }

    private fun hideStaffControls(view: WebView) {
        view.evaluateJavascript(
            """
            document.querySelectorAll('a[href*="/login"], .btn-guest-login, .btn-app-download').forEach(function (item) {
                item.style.display = 'none';
            });
            """.trimIndent(),
            null
        )
    }

    private fun createOfflineView(): View {
        val wrapper = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            gravity = Gravity.CENTER
            setPadding(40, 40, 40, 40)
            setBackgroundColor(Color.rgb(248, 250, 252))
            visibility = View.GONE
        }

        val title = TextView(this).apply {
            text = getString(R.string.offline_title)
            textSize = 22f
            setTextColor(Color.rgb(15, 23, 42))
            gravity = Gravity.CENTER
        }

        val message = TextView(this).apply {
            text = getString(R.string.offline_message)
            textSize = 15f
            setTextColor(Color.rgb(100, 116, 139))
            gravity = Gravity.CENTER
            setPadding(0, 12, 0, 24)
        }

        val retry = Button(this).apply {
            text = getString(R.string.retry)
            setOnClickListener {
                wrapper.visibility = View.GONE
                webView.loadUrl(customerUrl)
            }
        }

        wrapper.addView(title)
        wrapper.addView(message)
        wrapper.addView(retry)

        return wrapper
    }

    override fun onSaveInstanceState(outState: Bundle) {
        super.onSaveInstanceState(outState)
        webView.saveState(outState)
    }

    override fun onBackPressed() {
        if (webView.canGoBack()) {
            webView.goBack()
        } else {
            super.onBackPressed()
        }
    }
}