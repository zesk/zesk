var ckrpLibrary = {
    ckrislocal1: "://{{ip}}:{{request_port}}",
    ckrislocal2: "{{name}}:{{request_port}}",
    ckrphna    : "ws://{{ip}}:{{codekit_port}}",
    ckrphnba   : "ws://{{name}}:{{codekit_port}}",
    ckrpwssp   : "{{codekit_port}}",
    ckSEF      : 0
};
!function () {
	"use strict";
    function e(e, n, t) {
        var o = new RegExp("(\\?|\\&)" + n + "=.*?(?=(&|$))"),
            r = e
                .toString()
                .split("#"),
            a = r[0],
            i = r[1],
            l = /\?.+$/,
            s = a;
        return s = o.test(a)
            ? a.replace(o, "$1" + n + "=" + t)
            : l.test(a)
                ? a + "&" + n + "=" + t
                : a + "?" + n + "=" + t,
        i && (s += "#" + i),
        s
    }
    function n(e) {
        if ("undefined" == typeof e.nodeName) 
            return null;
        for (var n = []; null !== e.parentNode;) {
            for (var t = 0, o = 0, r = 0; r < e.parentNode.childNodes.length; r++) {
                var a = e.parentNode.childNodes[r];
                a.nodeName === e.nodeName && (a === e && (o = t), t++)
            }
            e.hasAttribute("id") && "" !== e.id
                ? n.unshift(e.nodeName.toLowerCase() + "#" + e.id)
                : t > 1
                    ? (o++, n.unshift(e.nodeName.toLowerCase() + ":nth-of-type(" + o + ")"))
                    : n.unshift(e.nodeName.toLowerCase()),
            n.unshift(">"),
            e = e.parentNode
        }
        return n.slice(3)
    }
    function t(e) {
        var n = document.location,
            t = new RegExp("^\\.|^/(?!/)|^[\\w]((?!://).)*$|" + n.host + "|" + ckrpLibrary.ckrislocal1 + "|" + ckrpLibrary.ckrislocal2);
        return e.match(t)
    }
    function o() {
        var e = document.body.parentNode;
        e.className = e
            .className
            .replace(/\s*lpcodekit\-loading/gi, "")
    }
    function r(e, n) {
        for (var t = n.styleSheets, o = t.length, a = 0; a < o; a++) {
            var i = t[a];
            i && e.push(i)
        }
        for (var l = n.querySelectorAll('link[rel="import"]'), s = l.length, c = 0; c < s; c++) {
            var d = l[c];
            d.import && r(e, d.import)
        }
    }
    function a(e) {
        return e
            .charAt(0)
            .toUpperCase() + e.slice(1)
    }
    function i(e, n, t, o, r, a) {
        t || (t = new Date);
        var i = "undefined" == typeof t
                ? ""
                : "; expires=" + t.toGMTString(),
            l = "undefined" == typeof o
                ? ""
                : "; path=" + o,
            s = "undefined" == typeof r
                ? ""
                : "; domain=" + r,
            c = "undefined" == typeof a
                ? ""
                : "; secure";
        document.cookie = e + "=" + encodeURIComponent(n) + i + l + s + c
    }
    function l(e) {
        var n = document
            .cookie
            .indexOf(";", e);
        return n === -1 && (n = document.cookie.length),
        decodeURIComponent(document.cookie.substring(e, n))
    }
    function s(e) {
        for (var n = e + "=", t = n.length, o = document.cookie.length, r = 0; r < o;) {
            var a = r + t;
            if (document.cookie.substring(r, a) === n) 
                return l(a);
            if (r = document.cookie.indexOf(" ", r) + 1, 0 === r) 
                break
        }
        return null
    }
    function c() {
        var e = new Date;
        e.setTime(e.getTime() + 864e5);
        var n = window.pageXOffset,
            t = window.pageYOffset,
            o = n + "_" + t;
        i("ckrp_ypos", o, e)
    }
    function d() {
        var e = s("ckrp_ypos");
        if (e) {
            var n = e.split("_");
            2 === n.length && window.scrollTo(parseInt(n[0]), parseInt(n[1])),
            i("ckrp_ypos", "0_0", new Date("January 01, 1970 01:01:00"))
        }
    }
    function u(n) {
        function a(e) {
            var n = document.getElementsByTagName("head")[0];
            if (e === !0) {
                if (!document.getElementById("LPCodeKitLiveTransitionRule")) {
                    var o = document.createElement("style"),
                        a = "transition: all .3s ease-out;",
                        i = [
                            ".lpcodekit-loading * { ",
                            a,
                            " -webkit-",
                            a,
                            "-moz-",
                            a,
                            "-o-",
                            a,
                            "}"
                        ].join("");
                    o.setAttribute("type", "text/css"),
                    o.setAttribute("id", "LPCodeKitLiveTransitionRule"),
                    n.appendChild(o),
                    o.styleSheet
                        ? o.styleSheet.cssText = i
                        : o.appendChild(document.createTextNode(i))
                }
            } else {
                var l = document.getElementById("LPCodeKitLiveTransitionRule");
                l && n.removeChild(l)
            }
            var s = [];
            r(s, document);
            for (var c = s.length, d = 0; d < c; d++) {
                var h = s[d];
                if (h) {
                    var f = h.media.mediaText,
                        g = h.href;
                    if (g && t(g)) {
                        var y = g.search(/ckMarkedForRemoval/i);
                        if (y === -1) {
                            var v = g.split("?"),
                                m = v[0],
                                b = v.length > 1
                                    ? v[1]
                                    : "";
                            b = b.replace(/(&)?now=[0-9]*/gi, ""),
                            b = b.length > 0
                                ? "?" + b + "&now=" + 1 * new Date
                                : "?now=" + 1 * new Date;
                            var k = h.ownerNode,
                                w = k.id,
                                C = document.body.parentNode,
                                S = k.ownerDocument,
                                x = S.createElement("link");
                            C.className = C
                                .className
                                .replace(/\s*lpcodekit\-loading/gi, "") + " lpcodekit-loading",
                            x.setAttribute("type", "text/css"),
                            x.setAttribute("rel", "stylesheet"),
                            x.setAttribute("href", m + b),
                            f && x.setAttribute("media", f),
                            w && x.setAttribute("id", w);
                            var L = k.parentNode,
                                T = k.nextSibling;
                            T
                                ? L.insertBefore(x, T)
                                : L.appendChild(x),
                            window.ShadowDOMPolyfill && (k = window.ShadowDOMPolyfill.wrapIfNeeded(k)),
                            u[m] = x,
                            p[m] = k
                        }
                    }
                }
            }
        }
        function i() {
            if (h > 100) {
                for (var e in p) {
                    var n = p[e];
                    n
                        .parentNode
                        .removeChild(n),
                    delete p[e]
                }
                return C = !0,
                o(),
                void console.warn("CodeKit told this page to refresh its stylesheets, but one or more did not downl" +
                        "oad correctly from the preview server. This is almost always caused by a laggy L" +
                        "AN. (Are you on public WiFi?) The page's state may not reflect your latest chang" +
                        "es. Reload it manually or save your file again to have CodeKit attempt another s" +
                        "tylesheet injection.")
            }
            for (var t in p) {
                var r = u[t],
                    a = p[t];
                try {
                    var l = r.sheet || r.styleSheet,
                        s;
                    if ("undefined" != typeof l && (s = l.rules || l.cssRules), !l || !s) 
                        return h++,
                        void setTimeout(i, 50);
                    s.length >= 0
                        ? (a.parentNode.removeChild(a), delete p[t], h = 0)
                        : (h++, setTimeout(i, 50))
                } catch (e) {
                    return h++,
                    void setTimeout(i, 50)
                }
            }
            var c = 0;
            for (var d in p) {
                ++c;
                break
            }
            0 === c && (C = !0, setTimeout(o, 300))
        }
        function l() {
            for (var e in p) {
                var n = p[e];
                n
                    .parentNode
                    .removeChild(n),
                delete p[e],
                setTimeout(o, 300)
            }
        }
        function s() {
            var e = navigator
                    .userAgent
                    .toLowerCase()
                    .indexOf("chrome") > -1,
                n = document
                    .URL
                    .indexOf("file://") > -1;
            e && n
                ? setTimeout(l, 400)
                : i()
        }
        function d() {
            C === !0 || S > 4
                ? (S = 0, y = !0)
                : (++S, setTimeout(d, 1500))
        }
        if (y === !1) 
            return void console.warn("A refresh was requested, but the page is already refreshing. The new refresh req" +
                    "uest was ignored.");
        C = !1,
        y = !1;
        var u = {},
            p = {},
            h = 0;
        switch ("undefined" != typeof ckinjectionnotpossible && (20 !== n && 30 !== n || (n = 40)), n) {
            case 20:
                a(!0),
                setTimeout(s, 70),
                setTimeout(d, 2e3);
                break;
            case 30:
                a(!1),
                setTimeout(s, 70),
                setTimeout(d, 2e3);
                break;
            case 40:
                var f = Math.round(+ new Date / 1e3),
                    g = e(window.location, "ckcachecontrol", f);
                c(),
                window
                    .location
                    .assign(g);
                break;
            case 50:
                var v = window.location.host;
                m
                    ? window
                        .location
                        .assign("http://" + v + "/" + m)
                    : window
                        .location
                        .assign("http://" + v)
        }
    }
    function p(e) {
        var n = document.querySelector(e.domTargetString),
            t = e
                .type
                .toLowerCase();
        if (null !== n) 
            switch (t) {
                case "click":
                    var o = new MouseEvent("click", {
                        bubbles   : e.bubbles,
                        cancelable: e.cancelable,
                        view      : window
                    });
                    o.ckSyncEvent = !0,
                    "label" === n
                        .tagName
                        .toLowerCase() && null !== n.htmlFor && (_ = !0),
                    n.dispatchEvent(o) === !1 && console.log("CodeKit tried to sync a click event from another browser on the following elemen" +
                            "t, but a handler in the event-bubbling chain canceled the event: " + e.domTargetString);
                    break;
                case "input":
                    var r = new Event("input", {
                        bubbles   : e.bubbles,
                        cancelable: e.cancelable,
                        detail    : e.detail,
                        view      : window
                    });
                    r.ckSyncEvent = !0,
                    n.value       = e.domTargetValue,
                    n.dispatchEvent(r) === !1 && console.log("CodeKit tried to sync an input event from another browser on the following eleme" +
                            "nt, but a handler in the event-bubbling chain canceled the event: " + e.domTargetString);
                    break;
                case "change":
                    break;
                case "focus":
                    q = !0,
                    n.focus();
                    break;
                case "blur":
                    z = !0,
                    n.blur();
                    break;
                case "keydown":
                case "keyup":
                    var a = new Event(t, {
                        altKey    : e.altKey,
                        bubbles   : e.bubbles,
                        cancelable: e.cancelable,
                        charCode  : e.charCode,
                        ctrlKey   : e.ctrlKey,
                        detail    : e.detail,
                        key       : e.key,
                        keyCode   : e.keyCode,
                        location  : e.location,
                        metaKey   : e.metaKey,
                        shiftKey  : e.shiftKey,
                        view      : window,
                        which     : e.which
                    });
                    a.ckSyncEvent = !0,
                    n.dispatchEvent(a) === !1 && console.log("CodeKit tried to sync a keydown event from another browser on the following elem" +
                            "ent, but a handler in the event-bubbling chain canceled the event: " + e.domTargetString);
                    break;
                default:
                    return
            }
        else {
            var i = "input" === t
                ? "an "
                : "a ";
            console.log("While trying to sync " + i + t + " event from another browser, CodeKit could not find a DOM element on this page m" +
                    "atching the following selector. (Is this browser viewing a different page than o" +
                    "thers? Did you use illegal characters such as : or / in an ID or class name?). E" +
                    "lement not found: " + e.domTargetString)
        }
    }
    function h() {
        z = !1,
        q = !1,
        H = null,
        W = null,
        _ = !1;
        var e = document.getElementsByTagName("body")[0];
        (ckrpLibrary.ckSEF & j.click) === j.click
            ? e.addEventListener("click", U, !0)
            : e.removeEventListener("click", U, !0),
        (ckrpLibrary.ckSEF & j.input) === j.input
            ? e.addEventListener("input", U, !0)
            : e.removeEventListener("input", U, !0),
        (ckrpLibrary.ckSEF & j.focus) === j.focus
            ? window.addEventListener("focus", U, !0)
            : window.removeEventListener("focus", U, !0),
        (ckrpLibrary.ckSEF & j.blur) === j.blur
            ? window.addEventListener("blur", U, !0)
            : window.removeEventListener("blur", U, !0),
        (ckrpLibrary.ckSEF & j.keydown) === j.keydown
            ? e.addEventListener("keydown", U, !0)
            : e.removeEventListener("keydown", U, !0),
        (ckrpLibrary.ckSEF & j.keyup) === j.keyup
            ? e.addEventListener("keyup", U, !0)
            : e.removeEventListener("keyup", U, !0)
    }
    function f() {
        function e(e) {
            var n = document.getElementById("lpckBannerContainer");
            n
                ? n.innerHTML = e
                : (n = document.createElement("div"), n.id = "lpckBannerContainer", n.setAttribute("style", 'display:block; position:fixed; top:0; left:0; width:100%; z-index:99999999; font' +
                        '-family: -apple-system, BlinkMacSystemFont, "Helvetica Neue", sans-serif; text-a' +
                        'lign: center; line-height: 1.6; padding:0 margin:0;'), n.innerHTML = e, document.body.firstChild
                    ? document.body.insertBefore(n, document.body.firstChild)
                    : document.body.appendChild(n))
        }
        function n(e) {
            var n = document.getElementById("lpckBannerContainer");
            n && n.firstElementChild.id === e && n
                .parentNode
                .removeChild(n)
        }
        function t() {
            var e = document.getElementById("lpckBannerContainer");
            null !== e && e
                .parentNode
                .removeChild(e)
        }
        function o(e) {
            var n = s("ckrp_sslManualRefresh");
            if ("true" === n) 
                console.warn("Cannot connect to CodeKit's refresh server over SSL (you opted to refresh manual" +
                        "ly, instead).");
            else if (!document.getElementById("ckx509v3AllWrapper")) {
                var t = document.createElement("div");
                t.id = "ckx509v3AllWrapper",
                t.setAttribute("style", "all:initial"),
                t.innerHTML = e,
                document.body.firstChild
                    ? document
                        .body
                        .insertBefore(t, document.body.firstChild)
                    : document
                        .body
                        .appendChild(t)
            }
        }
        function r() {
            var e = document.getElementById("ckx509v3AllWrapper");
            null !== e && e
                .parentNode
                .removeChild(e)
        }
        function a() {
            null === b && (b = setInterval(function () {
                try {
                    if (++k > 3) {
                        var n = s("ckrp_suppressLagBanner");
                        "true" !== n && e(N),
                        console.warn("CodeKit has not sent a message in over 15 seconds. The connection *might* have f" +
                                "ailed or your network could be laggy. Auto-refreshing may be slow and unreliable" +
                                ". You can try manually reloading the page to reset the connection.")
                    }
                } catch (e) {
                    clearInterval(b),
                    b = null,
                    k = 0,
                    console.warn("Exception in heartbeatInterval: " + e.message)
                }
            }, 5e3))
        }
        function i() {
            null !== b && (clearInterval(b), b = null, k = 0)
        }
        function l() {
            g.close()
        }
        window.WebSocket = window.WebSocket || window.MozWebSocket;
        var c = "https:" === window.location.protocol,
            y = c
                ? "wss"
                : "ws",
            w = y + "://" + window.location.hostname + ":" + ckrpLibrary.ckrpwssp;
        v++,
        g = new WebSocket(w, ["ckrp"]),
        setTimeout(function () {
            if (0 === g.readyState) {
                console.warn("Connecting to CodeKit's Refresh Server has taken longer than 3 seconds. (It shou" +
                        "ld be virtually instant on good networks.) Browser-refreshing and event-syncing " +
                        "may be laggy or unreliable on this network.");
                var n = s("ckrp_suppressConnectingBanner");
                "true" !== n && (document.getElementById("lpckBannerContainer") || e(A))
            }
        }, 3e3),
        g.onopen    = function () {
            v = 0,
            t(),
            a(),
            h()
        },
        g.onerror   = function (e) {
            "undefined" != typeof e.message && console.warn("Error connecting to CodeKit's refresh server: " + e.message),
            c && (/Firefox/i.test(navigator.userAgent) && !/Mobi/i.test(navigator.userAgent)
                ? (g.onclose = function () {},
                o(T))
                : /Android/i.test(navigator.userAgent)
                    ? (g.onclose = function () {},
                    o(E))
                    : (g.onclose = function () {},
                    o(K)))
        },
        g.onclose   = function (n) {
            return !n.wasClean && v < 1
                ? void f()
                : void(!n.wasClean && null === b && c
                    ? /iPhone|iPad|iPod/i.test(navigator.userAgent) && /Safari/i.test(navigator.userAgent) && /Mobi/i.test(navigator.userAgent)
                        ? o(L)
                        : /Safari/i.test(navigator.userAgent) && /Mac OS X/i.test(navigator.userAgent) && o(B)
                    : n.wasClean || (i(), e(O)))
        },
        g.onmessage = function (e) {
            var t = JSON.parse(e.data),
                o = t.c;
            switch (k = 0, n("lpckBannerContentLag"), o) {
                case "ia":
                    u(20);
                    break;
                case "ina":
                    u(30);
                    break;
                case "r":
                    u(40);
                    break;
                case "rtr":
                    m = t.rtrppa,
                    u(50);
                    break;
                case "BS":
                    p(t);
                    break;
                case "sUP":
                    ckrpLibrary.ckSEF = t.ckSEF,
                    h();
                    break;
                case "sd":
                    g.close()
            }
        },
        window.addEventListener("beforeunload", l, !1),
        window.addEventListener("load", d, !1)
    }
    var g = null,
        y = !0,
        v = 0,
        m = null,
        b = null,
        k = 0,
        w = !0,
        C = !0,
        S = 0,
        x = "ontouchstart" in window || navigator.maxTouchPoints
            ? "tap"
            : "click",
        L = null,
        T = null,
        E = null,
        B = null,
        K = null,
        N = null,
        O = null,
        A = null,
        F = "<hr style='border:none; border-top:1px solid #EEE; margin:40px 0 32px 0' />",
        I = "<a href='#' onclick='ckrpLibrary.x509v3ManualButtonHandler();return false;' styl" +
              "e='background:none; display: inline; color: #999'>I'll refresh manually</a>",
        M = "background:none; display:block; margin:0; padding:0; font-size:2vmin; font-weigh" +
              "t:700",
        D = "display:inline; background:#419BE2; color:#FFF; text-transform:uppercase; text-d" +
              "ecoration:none; padding:10px; font-weight:800; margin-right:10px",
        P = '<div style=\'font:2vmin/1.6 "system","-apple-system","BlinkMacSystemFont","Helve' +
              'tica Neue","Lucida Grande"; padding:16px; overflow-y:scroll; position:fixed; top' +
              ':0; left:0; right:0; bottom:0; background:rgba(255, 255, 255, 0.95); display:fle' +
              'x; flex-flow:column; align-items:center; justify-content:center\'><div style=\'w' +
              'idth:90%; max-width:600px; min-width:280px;\'><h1 style=\'font-size:5vmin; margi' +
              'n:0\'>SSL Browser Refreshing</h1><div>',
        R = null;
    R                                     = "\n<h2 style='" + M + "'>Security Warning</h2>\n<p style='margin: 0 0 24px 0; padding:0'>DO NOT rely on" +
            " this connection to protect sensitive data. It is for development testing only.<" +
            "/p>\n",
    L                                     = P + "\n<p style='margin: 0 0 48px 0; padding:0'>To auto-refresh iOS devices over SSL," +
            " CodeKit must install an SSL certificate. Tap the button, follow the \"install\"" +
            " prompts, then reload this page.</p>\n<a href='ckx509v35757.pem' style='" + D + "'>Install Certificate</a>\n" + I + "\n" + F + "\n<h2 style='" + M + "'>Mobile Safari Required</h2>\n<p style='margin: 0 0 24px 0; padding:0'>Installa" +
            "tion <span style='font-style: italic'>must</span> be done from Mobile Safari due" +
            " to iOS restrictions; it will fail in other browsers. Once the certificate is in" +
            "stalled, auto-refresh will work on all iOS browsers.</p>\n<h2 style='" + M + "'>Removal</h2>\n<p style='margin: 0 0 24px 0; padding:0'>To remove the certifica" +
            "te, go to <span style='font-style: italic'>Settings &gt; General &gt; Profiles.<" +
            "/span> CodeKit uses a new certificate whenever your Mac's IP address changes, so" +
            " you should remove old ones.</p>\n" + R + "\n<h2 style='" + M + "'>Other Options</h2>\n<p style='margin: 0 0 24px 0; padding:0'>Turn off SSL in C" +
            "odeKit's <span style='font-style:italic'>Server Popover</span>. Or, if you don't" +
            " install the certificate, manually refresh this device. For more info, <a href='" +
            "http://codekitapp.com/help' target='_blank' style='color:blue; display:inline; b" +
            "ackground:none;'>tap here</a>.</p> \n</div>  \n</div>\n</div>\n",
    T                                     = P + "\n<p style='margin: 0 0 48px 0; padding:0'>To auto-refresh Firefox over SSL, you" +
            " must approve the refresh server's connection. Click the button to open a new ta" +
            "b, add the security exception for that address, then reload this page.</p>\n<a h" +
            "ref='" + ("https://" + window.location.hostname + ":" + ckrpLibrary.ckrpwssp) + "' target='_blank' style='" + D + "'>Approve Connection</a>\n" + I + "\n" + F + "\n<h2 style='" + M + "'>Removal</h2>\n<p style='margin: 0 0 24px 0; padding:0'>To remove the security " +
            "exceptions, go to <span style='font-style: italic'>Preferences &gt; Advanced &gt" +
            "; Certificates.</span> CodeKit uses a new certificate whenever your Mac's IP add" +
            "ress changes, so you should remove old exceptions.</p>\n" + R + "\n<h2 style='" + M + "'>Other Options</h2>\n<p style='margin: 0 0 24px 0; padding:0'>Turn off SSL in C" +
            "odeKit's <span style='font-style:italic'>Server Popover</span>. Or, you can manu" +
            "ally refresh Firefox. For more info, <a href='http://codekitapp.com/help' target" +
            "='_blank' style='color:blue; display:inline; background:none;'>click here</a>.</" +
            "p> \n</div>  \n</div>\n</div>\n",
    E                                     = P + "\n<p style='margin: 0 0 48px 0; padding:0'>To auto-refresh Android over SSL, you" +
            " must approve the refresh server's connection. Tap the button to open a new tab," +
            " bypass the security warning to load that page, then reload this one.</p>\n<a hr" +
            "ef='" + ("https://" + window.location.hostname + ":" + ckrpLibrary.ckrpwssp) + "' target='_blank' style='" + D + "'>Approve Connection</a>\n" + I + "\n" + F + "\n" + R + "\n<h2 style='" + M + "'>Other Options</h2>\n<p style='margin: 0 0 24px 0; padding:0'>Turn off SSL in C" +
            "odeKit's <span style='font-style:italic'>Server Popover</span>. Or, you can manu" +
            "ally refresh Android devices. For more info, <a href='http://codekitapp.com/help" +
            "' target='_blank' style='color:blue; display:inline; background:none;'>tap here<" +
            "/a>.</p> \n</div>  \n</div>\n</div>\n",
    B                                     = P + "\n<p style='margin: 0 0 48px 0; padding:0'>To auto-refresh Safari over SSL you m" +
            "ust add a certificate to the OS X Keychain. Download the certificate, then doubl" +
            "e-click it to install. Afterwards, reload this page.</p>\n<a href='ckx509v35757." +
            "pem' target='_blank' style='" + D + "'>Download Certificate</a>\n" + I + "\n" + F + "\n<h2 style='" + M + "'>Removal</h2>\n<p style='margin: 0 0 24px 0; padding:0'>To remove the certifica" +
            "te, launch <span style='font-style: italic'>Keychain Access</span> and search fo" +
            "r \"CodeKit Temp Certificate\". CodeKit uses a new certificate whenever your Mac" +
            "'s IP address changes, so you should remove old ones.</p>\n" + R + "\n<h2 style='" + M + "'>Other Options</h2>\n<p style='margin: 0 0 24px 0; padding:0'>Turn off SSL in C" +
            "odeKit's <span style='font-style:italic'>Server Popover</span>. Or, use Chrome a" +
            "nd Firefox on OS X (neither requires installing a certificate). For more info, <" +
            "a href='http://codekitapp.com/help' target='_blank' style='color:blue; display:i" +
            "nline; background:none;'>click here</a>.</p> \n</div>  \n</div>\n</div>\n",
    K                                     = P + "\n<p style='margin: 0 0 48px 0; padding:0'>CodeKit cannot auto-refresh this brow" +
            "ser over SSL. You'll have to refresh it manually. " + a(x) + " this button to dismiss this notice for one hour.</p>\n<a href='#' onclick='ckrp" +
            "Library.x509v3ManualButtonHandler();return false;' style='" + D + "'>I'll Refresh Manually</a>\n" + F + "\n" + R + "\n<h2 style='" + M + "'>Other Options</h2>\n<p style='margin: 0 0 24px 0; padding:0'>Turn off SSL in C" +
            "odeKit's <span style='font-style:italic'>Server Popover</span>. Or, use a device" +
            " and browser that CodeKit can auto-refresh over SSL. Safari, Chrome and Firefox " +
            "are supported on all platforms. Android 4.3+, iOS 8+ and OS 10.10+ have all been" +
            " tested. For more info, <a href='http://codekitapp.com/help' target='_blank' sty" +
            "le='color:blue; display:inline; background:none;'>" + x + " here</a>.</p> \n</div>  \n</div>\n</div>\n",
    N                                     = "\n<div id='lpckBannerContentLag' onclick='ckrpLibrary.bannerClickHandler(\"lpckB" +
                                               "annerContentLag\", false); return false;' style='background: rgba(243, 165, 54, " +
                                               "0.97); border-bottom: 1px solid #d08c2a; color: #FFF; cursor: pointer; padding: " +
                                               "32px;'>\n<p style='font-size: 24px; font-weight: bold; margin: 0;'>Unstable Conn" +
                                               "ection</p>\n<p style='font-size: 18px; margin: 10px 0 0 0; line-height: 24px;'>C" +
                                               "odeKit may have trouble refreshing this page. Details are in the browser's conso" +
                                               "le. " + a(x) + " this banner to hide it or <a href='#' onclick='ckrpLibrary.bannerClickHandler(" +
            "\"lpckBannerContentLag\", true); return false;' style='display:inline; color: bl" +
            "ue; background:none; text-decoration:underline'>" + x + " here to suppress this warning for one hour.</a></p>\n</div>\n",
    O                                     = "\n<div id='lpckBannerContentDisconnected' onclick='ckrpLibrary.bannerClickHandle" +
                                               "r(\"lpckBannerContentDisconnected\", false); return false;' style='background: r" +
                                               "gba(217, 60, 60, 0.97); border-bottom: 1px solid #922c2c; color: #FFF; cursor: p" +
                                               "ointer; padding: 32px;'>\n<p style='font-size: 18px; font-weight: bold; margin: " +
                                               "0; line-height: 24px;'>This page has lost contact with CodeKit and will no longe" +
                                               "r auto-refresh. " + a(x) + " here to reload.</p>\n</div>\n",
    A                                     = "\n<div id='lpckBannerContentConnecting' onclick='ckrpLibrary.bannerClickHandler(" +
                                               "\"lpckBannerContentConnecting\", false); return false;' style='background: rgba(" +
                                               "145, 145, 145, 0.97); border-bottom: 1px solid #4d4d4d; color: #FFF; cursor: poi" +
                                               "nter; padding: 32px;'>\n<p style='font-size: 24px; font-weight: bold; margin: 0;" +
                                               "'>Connecting to CodeKit</p>\n<p style='font-size: 18px; margin: 10px 0 0 0; line" +
                                               "-height: 24px;'>Auto-refreshing won't work until this completes. " + a(x) + " this banner to hide it or <a href='#' onclick='ckrpLibrary.bannerClickHandler(" +
            "\"lpckBannerContentConnecting\", true); return false;' style='display:inline; co" +
            "lor: blue; background:none; text-decoration:underline'>" + x + " here to suppress this warning for one hour.</a></p>\n</div>\n",
    ckrpLibrary.x509v3ManualButtonHandler = function () {
        var e = new Date;
        e.setTime(e.getTime() + 36e5),
        i("ckrp_sslManualRefresh", !0, e);
        var n = document.getElementById("ckx509v3AllWrapper");
        null !== n && n
            .parentNode
            .removeChild(n)
    },
    ckrpLibrary.bannerClickHandler        = function (e, n) {
        if ("lpckBannerContentDisconnected" === e) 
            u(40);
        else {
            if (n === !0) {
                var t = new Date;
                t.setTime(t.getTime() + 36e5),
                "lpckBannerContentLag" === e
                    ? i("ckrp_suppressLagBanner", !0, t)
                    : "lpckBannerContentConnecting" === e && i("ckrp_suppressConnectingBanner", !0, t)
            }
            var o = document.getElementById("lpckBannerContainer");
            null !== o && o.firstElementChild.id === e && o
                .parentNode
                .removeChild(o)
        }
    };
    var _ = !1,
        H = null,
        W = null,
        z = !1,
        q = !1,
        U = function e(t) {
            if (t.ckSyncEvent !== !0) {
                var o = t
                        .type
                        .toLowerCase(),
                    r = t.target.tagName
                        ? t
                            .target
                            .tagName
                            .toLowerCase()
                        : null;
                if ("focus" === o && q === !0) 
                    return void(q = !1);
                if ("blur" === o && z === !0) 
                    return void(z = !1);
                var a = "click" === o;
                if (a === !0 && "label" === r && null !== t.target.htmlFor) 
                    _ = !0;
                else if (_ === !0 && a === !0) 
                    return void(_ = !1);
                if ("change" !== o || "select" === r || "keygen" === r) {
                    var i = n(t.target),
                        l = {
                            bubbles        : t.bubbles,
                            c              : "BS",
                            cancelable     : t.cancelable,
                            detail         : t.detail,
                            domTargetString: null === i
                                ? null
                                : i.join(" "),
                            type           : t.type
                        };
                    if ("blur" === o) 
                        return null === l.domTargetString
                            ? void(H = null)
                            : (H = l, void setTimeout(function () {
                                null !== H && 1 === g.readyState && g.send(JSON.stringify(l))
                            }, 10));
                    if ("focus" === o) 
                        return null === l.domTargetString
                            ? void(W = null)
                            : (W = l, void setTimeout(function () {
                                null !== W && 1 === g.readyState && g.send(JSON.stringify(l))
                            }, 10));
                    if ("input" === t.type.toLowerCase() && (l.domTargetValue = t.target.value), "change" === o) {
                        for (var s = [], c = t.target.options.length, d = 0; d < c; d++) 
                            t.target.options[d].selected === !0 && s.push(t.target.options[d].value);
                        l.selectedIndexes = s
                    }
                    "keydown" !== o && "keyup" !== o || (l.altKey = t.altKey, l.ctrlKey = t.ctrlKey, l.charCode = t.charCode, l.key = t.key, l.keyCode = t.keyCode, l.location = t.location, l.metaKey = t.metaKey, l.shiftKey = t.shiftKey, l.which = t.which),
                    1 === g.readyState && g.send(JSON.stringify(l))
                }
            }
        },
        j = {
            blur   : 16,
            change : 4,
            click  : 1,
            focus  : 8,
            input  : 2,
            keydown: 32,
            keyup  : 64
        };
    document.addEventListener("DOMContentLoaded", f, !1)
}();