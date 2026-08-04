package com.meras.userapp

import android.annotation.SuppressLint
import android.app.Activity
import android.app.DownloadManager
import android.content.ActivityNotFoundException
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Color
import android.graphics.drawable.GradientDrawable
import android.net.ConnectivityManager
import android.net.NetworkCapabilities
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.os.Environment
import android.util.TypedValue
import android.view.Gravity
import android.view.View
import android.view.ViewGroup
import android.webkit.CookieManager
import android.webkit.PermissionRequest
import android.webkit.URLUtil
import android.webkit.ValueCallback
import android.webkit.WebChromeClient
import android.webkit.WebResourceError
import android.webkit.WebResourceRequest
import android.webkit.WebSettings
import android.webkit.WebView
import android.webkit.WebViewClient
import android.widget.Button
import android.widget.FrameLayout
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.TextView
import android.widget.Toast
import androidx.activity.OnBackPressedCallback
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout
import com.google.firebase.messaging.FirebaseMessaging

class MainActivity : AppCompatActivity() {
    private lateinit var webView: WebView
    private lateinit var swipeRefreshLayout: SwipeRefreshLayout
    private lateinit var progressBar: ProgressBar
    private lateinit var offlineView: View
    private lateinit var bottomNav: LinearLayout
    private lateinit var fabChatbot: View
    private lateinit var chatOverlay: LinearLayout
    private lateinit var chatWebView: WebView
    private val customerUrl: String by lazy { getString(R.string.customer_url) }
    private var fcmToken: String? = null
    
    // File upload callback support for WebViews
    private var filePathCallback: ValueCallback<Array<Uri>>? = null

    private val fileChooserLauncher = registerForActivityResult(
        ActivityResultContracts.StartActivityForResult()
    ) { result ->
        if (filePathCallback == null) return@registerForActivityResult
        
        var results: Array<Uri>? = null
        if (result.resultCode == Activity.RESULT_OK) {
            val data = result.data
            if (data != null) {
                val dataString = data.dataString
                val clipData = data.clipData
                if (clipData != null) {
                    results = Array(clipData.itemCount) { i -> clipData.getItemAt(i).uri }
                } else if (dataString != null) {
                    results = arrayOf(Uri.parse(dataString))
                }
            }
        }
        filePathCallback?.onReceiveValue(results)
        filePathCallback = null
    }

    // Tab Views
    private val tabViews = ArrayList<LinearLayout>()

    @SuppressLint("SetJavaScriptEnabled", "ClickableViewAccessibility")
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

            // Top Horizontal Loading Progress Bar
            progressBar = ProgressBar(this, null, android.R.attr.progressBarStyleHorizontal).apply {
                layoutParams = LinearLayout.LayoutParams(
                    ViewGroup.LayoutParams.MATCH_PARENT,
                    dpToPx(3)
                )
                progressDrawable.setColorFilter(
                    Color.parseColor("#4f46e5"),
                    android.graphics.PorterDuff.Mode.SRC_IN
                )
                visibility = View.GONE
            }
            mainLayout.addView(progressBar)

            // Main content area
            val contentFrame = FrameLayout(this).apply {
                layoutParams = LinearLayout.LayoutParams(
                    ViewGroup.LayoutParams.MATCH_PARENT,
                    0,
                    1.0f
                )
            }

            // Pull-To-Refresh Layout
            swipeRefreshLayout = SwipeRefreshLayout(this).apply {
                layoutParams = ViewGroup.LayoutParams(
                    ViewGroup.LayoutParams.MATCH_PARENT,
                    ViewGroup.LayoutParams.MATCH_PARENT
                )
                setColorSchemeColors(Color.parseColor("#4f46e5"))
                setOnRefreshListener {
                    if (isNetworkAvailable()) {
                        webView.reload()
                    } else {
                        isRefreshing = false
                        Toast.makeText(this@MainActivity, "No internet connection", Toast.LENGTH_SHORT).show()
                    }
                }
            }

            webView = WebView(this)

            // Disable SwipeRefresh when WebView is scrolled down
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
                webView.setOnScrollChangeListener { _, _, scrollY, _, _ ->
                    swipeRefreshLayout.isEnabled = (scrollY == 0)
                }
            }

            offlineView = createOfflineView()

            swipeRefreshLayout.addView(webView, ViewGroup.LayoutParams(
                ViewGroup.LayoutParams.MATCH_PARENT,
                ViewGroup.LayoutParams.MATCH_PARENT
            ))

            contentFrame.addView(swipeRefreshLayout)
            contentFrame.addView(offlineView, FrameLayout.LayoutParams(
                ViewGroup.LayoutParams.MATCH_PARENT,
                ViewGroup.LayoutParams.MATCH_PARENT
            ))

            // Secondary WebView for Floating Chat
            chatWebView = WebView(this).apply {
                layoutParams = LinearLayout.LayoutParams(
                    ViewGroup.LayoutParams.MATCH_PARENT,
                    0,
                    1.0f
                )
                setupWebViewSettings(this)
                
                webViewClient = object : WebViewClient() {
                    override fun shouldOverrideUrlLoading(view: WebView, request: WebResourceRequest): Boolean {
                        return handleNavigation(request.url)
                    }
                }

                webChromeClient = object : WebChromeClient() {
                    override fun onShowFileChooser(
                        webView: WebView?,
                        filePathCallback: ValueCallback<Array<Uri>>?,
                        fileChooserParams: FileChooserParams?
                    ): Boolean {
                        return handleFileChooser(filePathCallback, fileChooserParams)
                    }
                }
            }

            // Chat Overlay Header / Title Bar
            val chatHeader = LinearLayout(this).apply {
                orientation = LinearLayout.HORIZONTAL
                layoutParams = LinearLayout.LayoutParams(
                    ViewGroup.LayoutParams.MATCH_PARENT,
                    dpToPx(56)
                )
                setBackgroundColor(Color.parseColor("#4f46e5"))
                gravity = Gravity.CENTER_VERTICAL
                setPadding(dpToPx(16), 0, dpToPx(16), 0)

                // Title Text
                addView(TextView(this@MainActivity).apply {
                    text = "💬 Live Chat Support"
                    setTextColor(Color.WHITE)
                    textSize = 16f
                    setTypeface(null, android.graphics.Typeface.BOLD)
                    layoutParams = LinearLayout.LayoutParams(
                        0,
                        ViewGroup.LayoutParams.WRAP_CONTENT,
                        1.0f
                    )
                })

                // Close Button ("✕")
                addView(TextView(this@MainActivity).apply {
                    text = "✕"
                    setTextColor(Color.WHITE)
                    textSize = 18f
                    setTypeface(null, android.graphics.Typeface.BOLD)
                    isClickable = true
                    setPadding(dpToPx(12), dpToPx(12), dpToPx(12), dpToPx(12))
                    
                    val outValue = TypedValue()
                    theme.resolveAttribute(android.R.attr.selectableItemBackgroundBorderless, outValue, true)
                    setBackgroundResource(outValue.resourceId)

                    setOnClickListener {
                        chatOverlay.visibility = View.GONE
                        chatWebView.onPause()
                    }
                })
            }

            // Combined Chat Overlay Container
            chatOverlay = LinearLayout(this).apply {
                orientation = LinearLayout.VERTICAL
                layoutParams = FrameLayout.LayoutParams(
                    ViewGroup.LayoutParams.MATCH_PARENT,
                    ViewGroup.LayoutParams.MATCH_PARENT
                ).apply {
                    topMargin = dpToPx(50)
                }
                
                val roundedBg = GradientDrawable().apply {
                    setColor(Color.WHITE)
                    val r = dpToPx(16).toFloat()
                    cornerRadii = floatArrayOf(r, r, r, r, 0f, 0f, 0f, 0f)
                }
                background = roundedBg
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
                    elevation = dpToPx(12).toFloat()
                }
                visibility = View.GONE

                addView(chatHeader)
                addView(chatWebView)
            }

            contentFrame.addView(chatOverlay)

            // Floating Chatbot Button (FAB)
            fabChatbot = FrameLayout(this).apply {
                layoutParams = FrameLayout.LayoutParams(
                    dpToPx(56),
                    dpToPx(56)
                ).apply {
                    gravity = Gravity.BOTTOM or Gravity.END
                    setMargins(0, 0, dpToPx(16), dpToPx(16))
                }
                
                // Replicate browser gradient background (linear 135deg top-left to bottom-right)
                val shape = GradientDrawable(
                    GradientDrawable.Orientation.TL_BR,
                    intArrayOf(Color.parseColor("#4f46e5"), Color.parseColor("#3730a3"))
                ).apply {
                    shape = GradientDrawable.OVAL
                }
                
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
                    val ripple = android.graphics.drawable.RippleDrawable(
                        android.content.res.ColorStateList.valueOf(Color.argb(70, 255, 255, 255)),
                        shape,
                        null
                    )
                    background = ripple
                    elevation = dpToPx(6).toFloat()
                } else {
                    background = shape
                }
                visibility = View.GONE
                
                // Replicate browser's sleek vector icon instead of a basic text emoji
                addView(android.widget.ImageView(this@MainActivity).apply {
                    setImageResource(R.drawable.ic_chatbot)
                    scaleType = android.widget.ImageView.ScaleType.CENTER_INSIDE
                    setPadding(dpToPx(14), dpToPx(14), dpToPx(14), dpToPx(14))
                    layoutParams = FrameLayout.LayoutParams(
                        ViewGroup.LayoutParams.MATCH_PARENT,
                        ViewGroup.LayoutParams.MATCH_PARENT
                    )
                })

                setOnClickListener {
                    val rootBaseUrl = customerUrl.substringBefore("/user-app").substringBefore("?")
                    val chatUrl = getFinalUrlWithToken("$rootBaseUrl/chat")
                    
                    chatWebView.onResume()
                    chatWebView.loadUrl(chatUrl)
                    chatOverlay.visibility = View.VISIBLE
                }
            }

            contentFrame.addView(fabChatbot)

            // Bottom Navigation Bar
            bottomNav = createBottomNavigationBar()

            mainLayout.addView(contentFrame)
            mainLayout.addView(bottomNav)

            setContentView(mainLayout)

            // Enable Cookies
            CookieManager.getInstance().apply {
                setAcceptCookie(true)
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
                    setAcceptThirdPartyCookies(webView, true)
                }
            }

            setupWebViewSettings(webView)

            // Attach Download Listeners
            webView.setDownloadListener { url, userAgent, contentDisposition, mimeType, _ ->
                downloadFile(url, userAgent, contentDisposition, mimeType)
            }
            chatWebView.setDownloadListener { url, userAgent, contentDisposition, mimeType, _ ->
                downloadFile(url, userAgent, contentDisposition, mimeType)
            }

            webView.webChromeClient = object : WebChromeClient() {
                override fun onProgressChanged(view: WebView?, newProgress: Int) {
                    if (newProgress < 100) {
                        progressBar.visibility = View.VISIBLE
                        progressBar.progress = newProgress
                    } else {
                        progressBar.visibility = View.GONE
                    }
                }

                override fun onShowFileChooser(
                    webView: WebView?,
                    filePathCallback: ValueCallback<Array<Uri>>?,
                    fileChooserParams: FileChooserParams?
                ): Boolean {
                    return handleFileChooser(filePathCallback, fileChooserParams)
                }

                override fun onPermissionRequest(request: PermissionRequest?) {
                    request?.grant(request.resources)
                }
            }

            webView.webViewClient = object : WebViewClient() {
                override fun shouldOverrideUrlLoading(view: WebView, request: WebResourceRequest): Boolean {
                    return handleNavigation(request.url)
                }

                override fun onPageFinished(view: WebView, url: String) {
                    swipeRefreshLayout.isRefreshing = false
                    offlineView.visibility = View.GONE
                    hideStaffControls(view)
                    
                    CookieManager.getInstance().flush()
                    
                    view.evaluateJavascript("window.isLoggedIn") { value ->
                        val cleanValue = value?.replace("\"", "")?.trim()
                        val isLoggedIn = cleanValue == "true"
                        bottomNav.visibility = View.VISIBLE
                        if (url.contains("/chat")) {
                            fabChatbot.visibility = View.GONE
                        } else {
                            fabChatbot.visibility = View.VISIBLE
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
                        swipeRefreshLayout.isRefreshing = false
                        if (!isNetworkAvailable()) {
                            offlineView.visibility = View.VISIBLE
                        }
                    }
                }
            }

            // Modern Android Back Navigation handler
            onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
                override fun handleOnBackPressed() {
                    if (::chatOverlay.isInitialized && chatOverlay.visibility == View.VISIBLE) {
                        if (chatWebView.canGoBack()) {
                            chatWebView.goBack()
                        } else {
                            chatOverlay.visibility = View.GONE
                            chatWebView.onPause()
                        }
                    } else if (webView.canGoBack()) {
                        webView.goBack()
                    } else {
                        isEnabled = false
                        onBackPressedDispatcher.onBackPressed()
                    }
                }
            })

            // Notification Permission
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
                if (checkSelfPermission(android.Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) {
                    requestPermissions(arrayOf(android.Manifest.permission.POST_NOTIFICATIONS), 101)
                }
            }

            fetchFcmTokenAndLoad(savedInstanceState)

        } catch (t: Throwable) {
            Toast.makeText(this, "Startup error: ${t.message}", Toast.LENGTH_LONG).show()
            t.printStackTrace()
        }
    }

    private fun setupWebViewSettings(targetWebView: WebView) {
        targetWebView.settings.apply {
            javaScriptEnabled = true
            domStorageEnabled = true
            databaseEnabled = true
            loadsImagesAutomatically = true
            cacheMode = WebSettings.LOAD_DEFAULT
            mixedContentMode = WebSettings.MIXED_CONTENT_COMPATIBILITY_MODE
            setSupportZoom(false)
            allowFileAccess = true
            allowContentAccess = true
            useWideViewPort = true
            loadWithOverviewMode = true
            mediaPlaybackRequiresUserGesture = false

            val defaultUserAgent = userAgentString
            val customUserAgent = defaultUserAgent
                .replace("; wv", "")
                .replace(Regex("Version/[0-9.]+\\s"), "") + " MerasUserApp/1.0"
            userAgentString = customUserAgent
        }
    }

    private fun handleFileChooser(
        filePathCallback: ValueCallback<Array<Uri>>?,
        fileChooserParams: WebChromeClient.FileChooserParams?
    ): Boolean {
        this.filePathCallback?.onReceiveValue(null)
        this.filePathCallback = filePathCallback

        val intent = fileChooserParams?.createIntent() ?: Intent(Intent.ACTION_GET_CONTENT).apply {
            addCategory(Intent.CATEGORY_OPENABLE)
            type = "*/*"
        }

        try {
            fileChooserLauncher.launch(intent)
        } catch (e: ActivityNotFoundException) {
            this.filePathCallback = null
            Toast.makeText(this, "Cannot open file chooser", Toast.LENGTH_SHORT).show()
            return false
        }
        return true
    }

    private fun downloadFile(url: String, userAgent: String, contentDisposition: String, mimeType: String) {
        try {
            val request = DownloadManager.Request(Uri.parse(url)).apply {
                setMimeType(mimeType)
                addRequestHeader("User-Agent", userAgent)
                addRequestHeader("Cookie", CookieManager.getInstance().getCookie(url))
                setDescription("Downloading file...")
                setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED)
                val fileName = URLUtil.guessFileName(url, contentDisposition, mimeType)
                setTitle(fileName)
                setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, fileName)
            }
            val dm = getSystemService(Context.DOWNLOAD_SERVICE) as DownloadManager
            dm.enqueue(request)
            Toast.makeText(this, "Downloading file...", Toast.LENGTH_SHORT).show()
        } catch (e: Exception) {
            Toast.makeText(this, "Download failed: ${e.message}", Toast.LENGTH_SHORT).show()
        }
    }

    private fun isNetworkAvailable(): Boolean {
        val connectivityManager = getSystemService(Context.CONNECTIVITY_SERVICE) as ConnectivityManager
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            val nw = connectivityManager.activeNetwork ?: return false
            val actNw = connectivityManager.getNetworkCapabilities(nw) ?: return false
            return actNw.hasTransport(NetworkCapabilities.TRANSPORT_WIFI) ||
                   actNw.hasTransport(NetworkCapabilities.TRANSPORT_CELLULAR) ||
                   actNw.hasTransport(NetworkCapabilities.TRANSPORT_ETHERNET)
        } else {
            @Suppress("DEPRECATION")
            val nwInfo = connectivityManager.activeNetworkInfo
            @Suppress("DEPRECATION")
            return nwInfo != null && nwInfo.isConnected
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
        val scheme = uri.scheme.orEmpty().lowercase()
        if (scheme != "http" && scheme != "https") {
            try {
                val intent = Intent(Intent.ACTION_VIEW, uri)
                startActivity(intent)
            } catch (e: ActivityNotFoundException) {
                Toast.makeText(this, "No application found to handle action", Toast.LENGTH_SHORT).show()
            }
            return true
        }

        return false
    }

    private fun hideStaffControls(view: WebView) {
        view.evaluateJavascript(
            """
            (function() {
                // Hide all app-download buttons (web version download prompts) 
                // since the user already has the app installed
                var selectors = [
                    '.btn-app-download',
                    '.btn-app-download-hero',
                    '.btn-app-download-footer',
                    'a[href*="/download/android-app"]',
                    'a[href*="meras-user-app"]'
                ];
                selectors.forEach(function(sel) {
                    document.querySelectorAll(sel).forEach(function(el) {
                        el.style.display = 'none';
                    });
                });
            })();
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
            visibility = View.VISIBLE
            
            val border = GradientDrawable().apply {
                setColor(Color.WHITE)
                setStroke(dpToPx(1), Color.parseColor("#e2e8f0"))
            }
            background = border
        }

        val tabs = listOf(
            TabItem(R.drawable.ic_nav_home, "Home", "home"),
            TabItem(R.drawable.ic_nav_products, "Products", "products"),
            TabItem(R.drawable.ic_nav_inquiry, "Inquiry", "inquiry"),
            TabItem(R.drawable.ic_nav_profile, "Profile", "profile"),
            TabItem(R.drawable.ic_nav_alerts, "Alerts", "alerts")
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
                
                val outValue = TypedValue()
                theme.resolveAttribute(android.R.attr.selectableItemBackground, outValue, true)
                setBackgroundResource(outValue.resourceId)

                setOnClickListener {
                    onTabSelected(tab.id)
                }
            }

            val iconView = android.widget.ImageView(this).apply {
                setImageResource(tab.iconResId)
                layoutParams = LinearLayout.LayoutParams(dpToPx(22), dpToPx(22))
                setColorFilter(Color.parseColor("#64748b"))
            }

            val titleView = TextView(this).apply {
                text = tab.title
                textSize = 11f
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
            "inquiry" -> getFinalUrlWithToken(customerUrl) + "#inquire"
            "profile" -> getFinalUrlWithToken("$rootBaseUrl/profile")
            "alerts" -> getFinalUrlWithToken("$rootBaseUrl/notifications")
            else -> customerUrl
        }
        webView.loadUrl(targetUrl)
    }

    private fun highlightActiveTab(url: String) {
        val activeColor = Color.parseColor("#4f46e5")
        val inactiveColor = Color.parseColor("#64748b")

        val activeTabId = when {
            url.contains("/profile") -> "profile"
            url.contains("/notifications") -> "alerts"
            url.contains("#products") -> "products"
            url.contains("#inquire") -> "inquiry"
            else -> "home"
        }

        val tabs = listOf("home", "products", "inquiry", "profile", "alerts")
        val activeIndex = tabs.indexOf(activeTabId)

        for (i in tabViews.indices) {
            val tabLayout = tabViews[i]
            val iconView = tabLayout.getChildAt(0) as android.widget.ImageView
            val titleView = tabLayout.getChildAt(1) as TextView
            if (i == activeIndex) {
                iconView.setColorFilter(activeColor)
                titleView.setTextColor(activeColor)
                titleView.paint.isFakeBoldText = true
            } else {
                iconView.setColorFilter(inactiveColor)
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

    override fun onPause() {
        super.onPause()
        CookieManager.getInstance().flush()
    }

    override fun onSaveInstanceState(outState: Bundle) {
        super.onSaveInstanceState(outState)
        webView.saveState(outState)
    }

    private data class TabItem(val iconResId: Int, val title: String, val id: String)
}
