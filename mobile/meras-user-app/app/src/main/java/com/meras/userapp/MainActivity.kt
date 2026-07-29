package com.meras.userapp

import android.annotation.SuppressLint
import android.app.Activity
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Color
import android.graphics.drawable.GradientDrawable
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.util.TypedValue
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
import com.google.firebase.messaging.FirebaseMessaging

class MainActivity : Activity() {
    private lateinit var webView: WebView
    private lateinit var offlineView: View
    private lateinit var bottomNav: LinearLayout
    private val customerUrl: String by lazy { getString(R.string.customer_url) }
    private var fcmToken: String? = null
    
    // Tab Views
    private val tabViews = ArrayList<LinearLayout>()

    @SuppressLint("SetJavaScriptEnabled")
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        try {
            // Main vertical container
            val mainLayout = LinearLayout(this).apply {
                orientation = LinearLayout.VERTICAL
                layoutParams = ViewGroup.LayoutParams(
                    ViewGroup.LayoutParams.MATCH_PARENT,
                    ViewGroup.LayoutParams.MATCH_PARENT
                )
            }

            // Main content area
            val contentFrame = FrameLayout(this).apply {
                layoutParams = LinearLayout.LayoutParams(
                    ViewGroup.LayoutParams.MATCH_PARENT,
                    0,
                    1.0f
                )
            }

            webView = WebView(this)
            offlineView = createOfflineView()

            contentFrame.addView(webView, FrameLayout.LayoutParams(
                ViewGroup.LayoutParams.MATCH_PARENT,
                ViewGroup.LayoutParams.MATCH_PARENT
            ))
            contentFrame.addView(offlineView, FrameLayout.LayoutParams(
                ViewGroup.LayoutParams.MATCH_PARENT,
                ViewGroup.LayoutParams.MATCH_PARENT
            ))

            // Bottom Navigation Bar
            bottomNav = createBottomNavigationBar()

            mainLayout.addView(contentFrame)
            mainLayout.addView(bottomNav)

            setContentView(mainLayout)

            // Enable Cookies and third-party cookies for Google Sign-In
            android.webkit.CookieManager.getInstance().apply {
                setAcceptCookie(true)
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
                    setAcceptThirdPartyCookies(webView, true)
                }
            }

            webView.settings.apply {
                javaScriptEnabled = true
                domStorageEnabled = true
                databaseEnabled = true
                loadsImagesAutomatically = true
                cacheMode = WebSettings.LOAD_DEFAULT
                mixedContentMode = WebSettings.MIXED_CONTENT_COMPATIBILITY_MODE
                setSupportZoom(false)

                // Workaround for Google OAuth "disallowed_useragent" error inside WebViews:
                // We modify the User-Agent to remove "; wv" and the "Version/x.x" string,
                // which lets Google identify this WebView as a standard mobile Chrome browser.
                val defaultUserAgent = userAgentString
                val customUserAgent = defaultUserAgent
                    .replace("; wv", "")
                    .replace(Regex("Version/[0-9.]+\\s"), "")
                userAgentString = customUserAgent
            }

            webView.webViewClient = object : WebViewClient() {
                override fun shouldOverrideUrlLoading(view: WebView, request: WebResourceRequest): Boolean {
                    return handleNavigation(request.url)
                }

                override fun onPageFinished(view: WebView, url: String) {
                    offlineView.visibility = View.GONE
                    hideStaffControls(view)
                    
                    // Evaluate login state from web app to hide/show bottom navigation dynamically
                    view.evaluateJavascript("window.isLoggedIn") { value ->
                        val cleanValue = value?.replace("\"", "")?.trim()
                        val isLoggedIn = cleanValue == "true"
                        if (isLoggedIn) {
                            bottomNav.visibility = View.VISIBLE
                        } else {
                            bottomNav.visibility = View.GONE
                        }
                    }

                    highlightActiveTab(url)
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

            // Request Notification Permission on Android 13+
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
                if (checkSelfPermission(android.Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) {
                    requestPermissions(arrayOf(android.Manifest.permission.POST_NOTIFICATIONS), 101)
                }
            }

            // Fetch Firebase Token and load URL
            fetchFcmTokenAndLoad(savedInstanceState)

        } catch (t: Throwable) {
            Toast.makeText(this, "Startup error: ${t.message}", Toast.LENGTH_LONG).show()
            t.printStackTrace()
        }
    }

    private fun fetchFcmTokenAndLoad(savedInstanceState: Bundle?) {
        FirebaseMessaging.getInstance().token.addOnCompleteListener { task ->
            if (task.isSuccessful) {
                fcmToken = task.result
            }
            
            val finalUrl = getFinalUrlWithToken(customerUrl)

            if (savedInstanceState == null) {
                webView.loadUrl(finalUrl)
            } else {
                webView.restoreState(savedInstanceState)
            }
        }
    }

    private fun getFinalUrlWithToken(baseUrl: String): String {
        return if (fcmToken != null) {
            val uri = Uri.parse(baseUrl)
            val builder = uri.buildUpon()
            builder.appendQueryParameter("fcm_token", fcmToken)
            builder.build().toString()
        } else {
            baseUrl
        }
    }

    private fun handleNavigation(uri: Uri): Boolean {
        val scheme = uri.scheme.orEmpty()
        if (scheme == "mailto" || scheme == "tel") {
            startActivity(Intent(Intent.ACTION_VIEW, uri))
            return true
        }

        return false
    }

    private fun hideStaffControls(view: WebView) {
        view.evaluateJavascript(
            """
            document.querySelectorAll('.btn-app-download').forEach(function (item) {
                item.style.display = 'none';
            });
            """.trimIndent(),
            null
        )
    }

    private fun createBottomNavigationBar(): LinearLayout {
        val navBar = LinearLayout(this).apply {
            orientation = LinearLayout.HORIZONTAL
            layoutParams = LinearLayout.LayoutParams(
                ViewGroup.LayoutParams.MATCH_PARENT,
                dpToPx(65)
            )
            setBackgroundColor(Color.WHITE)
            gravity = Gravity.CENTER_VERTICAL
            visibility = View.GONE // Initially hidden to avoid flickering before page finished load
            
            // Add a thin top border shadow
            val border = GradientDrawable().apply {
                setColor(Color.WHITE)
                setStroke(dpToPx(1), Color.parseColor("#e2e8f0"))
            }
            background = border
        }

        val tabs = listOf(
            TabItem("🏠", "Home", "home"),
            TabItem("🛍️", "Products", "products"),
            TabItem("✉️", "Inquiry", "inquiry"),
            TabItem("💬", "Chat", "chat"),
            TabItem("👤", "Profile", "profile"),
            TabItem("🔔", "Alerts", "alerts")
        )

        for (i in tabs.indices) {
            val tab = tabs[i]
            val tabLayout = LinearLayout(this).apply {
                orientation = LinearLayout.VERTICAL
                gravity = Gravity.CENTER
                layoutParams = LinearLayout.LayoutParams(
                    0,
                    ViewGroup.LayoutParams.MATCH_PARENT,
                    1.0f
                )
                setPadding(0, dpToPx(6), 0, dpToPx(6))
                isClickable = true
                
                // Add a simple Ripple effect or background selector
                val outValue = TypedValue()
                theme.resolveAttribute(android.R.attr.selectableItemBackground, outValue, true)
                setBackgroundResource(outValue.resourceId)

                setOnClickListener {
                    onTabSelected(tab.id)
                }
            }

            val iconView = TextView(this).apply {
                text = tab.icon
                textSize = 17f
                gravity = Gravity.CENTER
            }

            val titleView = TextView(this).apply {
                text = tab.title
                textSize = 10f
                setTextColor(Color.parseColor("#64748b"))
                gravity = Gravity.CENTER
                setPadding(0, dpToPx(2), 0, 0)
            }

            tabLayout.addView(iconView)
            tabLayout.addView(titleView)
            
            navBar.addView(tabLayout)
            tabViews.add(tabLayout)
        }

        return navBar
    }

    private fun onTabSelected(tabId: String) {
        val rootBaseUrl = customerUrl.substringBefore("/user-app").substringBefore("?")
        val targetUrl = when (tabId) {
            "home" -> getFinalUrlWithToken(customerUrl)
            "products" -> getFinalUrlWithToken(customerUrl) + "#products"
            "inquiry" -> getFinalUrlWithToken(customerUrl) + "#inquiries"
            "chat" -> getFinalUrlWithToken("$rootBaseUrl/chat")
            "profile" -> getFinalUrlWithToken("$rootBaseUrl/profile")
            "alerts" -> getFinalUrlWithToken("$rootBaseUrl/notifications")
            else -> customerUrl
        }
        webView.loadUrl(targetUrl)
    }

    private fun highlightActiveTab(url: String) {
        val activeColor = Color.parseColor("#4f46e5") // Indigo
        val inactiveColor = Color.parseColor("#64748b") // Gray

        val activeTabId = when {
            url.contains("/profile") -> "profile"
            url.contains("/notifications") -> "alerts"
            url.contains("/chat") -> "chat"
            url.contains("#products") -> "products"
            url.contains("#inquiries") -> "inquiry"
            else -> "home"
        }

        val tabs = listOf("home", "products", "inquiry", "chat", "profile", "alerts")
        val activeIndex = tabs.indexOf(activeTabId)

        for (i in tabViews.indices) {
            val tabLayout = tabViews[i]
            val titleView = tabLayout.getChildAt(1) as TextView
            if (i == activeIndex) {
                titleView.setTextColor(activeColor)
                titleView.paint.isFakeBoldText = true
            } else {
                titleView.setTextColor(inactiveColor)
                titleView.paint.isFakeBoldText = false
            }
            titleView.invalidate()
        }
    }

    private fun dpToPx(dp: Int): Int {
        return (dp * resources.displayMetrics.density).toInt()
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
                webView.loadUrl(getFinalUrlWithToken(customerUrl))
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

    private data class TabItem(val icon: String, val title: String, val id: String)
}
