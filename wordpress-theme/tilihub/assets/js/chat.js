/**
 * TiliHub – kevyt chat (WhatsApp-tyylinen).
 * Hakee vastaukset sivuston omasta sisällöstä (REST: tilihub/v1/chat) ja tarjoaa
 * aina jatkon WhatsAppissa niin, että kävijän kysymys on valmiina viestissä.
 */
(function () {
  "use strict";
  var C = window.TILIHUB_CHAT;
  if (!C || document.getElementById("th-chat")) return;

  var lastQuestion = "";

  function el(tag, attrs, children) {
    var n = document.createElement(tag);
    if (attrs) {
      Object.keys(attrs).forEach(function (k) {
        if (k === "text") n.textContent = attrs[k];
        else if (k === "class") n.className = attrs[k];
        else n.setAttribute(k, attrs[k]);
      });
    }
    (children || []).forEach(function (c) {
      if (c) n.appendChild(typeof c === "string" ? document.createTextNode(c) : c);
    });
    return n;
  }

  function waUrl(text) {
    return "https://wa.me/" + C.whatsapp + "?text=" + encodeURIComponent(text || "Hei! Tarvitsisin lisätietoja.");
  }

  var WA_ICON =
    '<svg viewBox="0 0 32 32" aria-hidden="true" focusable="false"><path fill="currentColor" d="M16 3a13 13 0 0 0-11.1 19.7L3 29l6.5-1.8A13 13 0 1 0 16 3Zm0 23.6c-2 0-3.9-.5-5.6-1.5l-.4-.2-3.9 1 1-3.8-.2-.4A10.6 10.6 0 1 1 16 26.6Zm5.8-7.9c-.3-.2-1.9-.9-2.2-1s-.5-.2-.7.2l-1 1.2c-.2.2-.4.2-.7.1a8.7 8.7 0 0 1-4.3-3.8c-.3-.6.3-.5.9-1.7.1-.2 0-.4 0-.5l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4a3.3 3.3 0 0 0-1 2.4c0 1.4 1 2.8 1.2 3s2 3.1 4.9 4.3c1.8.8 2.5.8 3.4.7.6-.1 1.9-.8 2.1-1.5s.3-1.4.2-1.5c0-.2-.3-.3-.5-.4Z"/></svg>';

  // --- rakenne ---------------------------------------------------------------
  var launcher = el("button", { class: "th-chat-launcher", type: "button", "aria-expanded": "false", "aria-controls": "th-chat-panel" });
  launcher.innerHTML =
    '<span class="th-chat-launcher__icon">' + WA_ICON + '</span><span class="th-chat-launcher__label">Kysy meiltä</span>';

  var log = el("div", { class: "th-chat-log", role: "log", "aria-live": "polite", "aria-label": "Keskustelu" });
  var input = el("input", {
    id: "th-chat-input",
    class: "th-chat-input",
    type: "text",
    maxlength: "300",
    autocomplete: "off",
    placeholder: "Kirjoita kysymys…",
    "aria-label": "Kysymyksesi",
  });
  var send = el("button", { class: "th-chat-send", type: "submit", "aria-label": "Lähetä" });
  send.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3.4 20.4 21 12 3.4 3.6 3.4 10l12.6 2-12.6 2z"/></svg>';
  var form = el("form", { class: "th-chat-form" }, [input, send]);

  var avatar = el("span", { class: "th-chat-avatar", "aria-hidden": "true" });
  var img = el("img", { src: C.avatar, alt: "" });
  img.addEventListener("error", function () {
    avatar.classList.add("is-fallback");
    avatar.textContent = "TH";
  });
  avatar.appendChild(img);

  var close = el("button", { class: "th-chat-close", type: "button", "aria-label": "Sulje chat" });
  close.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M18.3 5.7 12 12l6.3 6.3-1.4 1.4L10.6 13.4 4.3 19.7 2.9 18.3 9.2 12 2.9 5.7 4.3 4.3l6.3 6.3 6.3-6.3z"/></svg>';

  var head = el("div", { class: "th-chat-head" }, [
    avatar,
    el("div", { class: "th-chat-who" }, [
      el("strong", { text: C.name }),
      el("span", { text: C.status }),
    ]),
    close,
  ]);

  var waFooter = el("a", { class: "th-chat-wa-main", href: waUrl(), target: "_blank", rel: "noopener" });
  waFooter.innerHTML = WA_ICON + "<span>Jatka WhatsAppissa</span>";

  var panel = el(
    "div",
    { id: "th-chat-panel", class: "th-chat-panel", role: "dialog", "aria-modal": "false", "aria-label": "TiliHub-chat", hidden: "" },
    [head, log, el("div", { class: "th-chat-bottom" }, [form, waFooter])]
  );
  var root = el("div", { id: "th-chat", class: "th-chat" }, [panel, launcher]);
  document.body.appendChild(root);

  // --- viestit ---------------------------------------------------------------
  function time() {
    var d = new Date();
    return ("0" + d.getHours()).slice(-2) + "." + ("0" + d.getMinutes()).slice(-2);
  }

  function bubble(who, nodes) {
    var b = el("div", { class: "th-chat-msg th-chat-msg--" + who }, nodes.concat([el("span", { class: "th-chat-time", text: time() })]));
    log.appendChild(b);
    log.scrollTop = log.scrollHeight;
    return b;
  }

  function quickReplies() {
    var wrap = el("div", { class: "th-chat-quick" });
    (C.quick || []).forEach(function (q) {
      var b = el("button", { type: "button", text: q });
      b.addEventListener("click", function () {
        ask(q);
      });
      wrap.appendChild(b);
    });
    log.appendChild(wrap);
  }

  function typing() {
    return bubble("bot", [el("span", { class: "th-chat-typing", "aria-label": "Kirjoittaa" }, [el("i"), el("i"), el("i")])]);
  }

  function answer(data) {
    var parts = [];
    if (data.heading) parts.push(el("strong", { class: "th-chat-heading", text: data.heading }));
    parts.push(el("p", { text: data.answer }));
    if (data.source && data.source.url) {
      parts.push(el("a", { class: "th-chat-source", href: data.source.url, text: "Lue lisää: " + data.source.title + " →" }));
    }
    if (data.related && data.related.length) {
      var rel = el("div", { class: "th-chat-related" }, [el("span", { text: "Katso myös" })]);
      data.related.forEach(function (r) {
        rel.appendChild(el("a", { href: r.url, text: r.title }));
      });
      parts.push(rel);
    }
    var wa = el("a", { class: "th-chat-wa", href: waUrl(data.wa_text), target: "_blank", rel: "noopener" });
    wa.innerHTML = WA_ICON + "<span>Kysy tarkemmin WhatsAppissa</span>";
    parts.push(wa);
    bubble("bot", parts);
    waFooter.href = waUrl(data.wa_text);
  }

  function ask(q) {
    q = (q || "").trim();
    if (!q) return;
    lastQuestion = q;
    var quick = log.querySelector(".th-chat-quick");
    if (quick) quick.remove();
    bubble("user", [el("p", { text: q })]);
    input.value = "";
    var t = typing();
    var started = Date.now();
    fetch(C.endpoint + (C.endpoint.indexOf("?") > -1 ? "&" : "?") + "q=" + encodeURIComponent(q), {
      headers: { Accept: "application/json" },
      credentials: "omit",
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        // Pieni viive tekee vastauksesta luontevan, mutta ei hidasta turhaan.
        var wait = Math.max(0, 450 - (Date.now() - started));
        setTimeout(function () {
          t.remove();
          if (data && data.answer) answer(data);
          else if (data && data.error) fail(data.error);
          else fail();
        }, wait);
      })
      .catch(function () {
        t.remove();
        fail();
      });
  }

  function fail(msg) {
    answer({
      answer: (msg ? msg + " " : "Yhteydessä oli hetkellinen ongelma. ") + "Voit kysyä suoraan WhatsAppissa tai soittaa " + C.phone + ".",
      wa_text: lastQuestion ? 'Hei! Kysyin sivuillanne: "' + lastQuestion + '"' : "",
    });
  }

  // --- avaus ja sulkeminen -----------------------------------------------------
  var started = false;
  function open() {
    panel.hidden = false;
    root.classList.add("is-open");
    launcher.setAttribute("aria-expanded", "true");
    if (!started) {
      started = true;
      bubble("bot", [el("p", { text: C.greeting })]);
      quickReplies();
    }
    setTimeout(function () {
      input.focus();
    }, 50);
  }
  function shut() {
    panel.hidden = true;
    root.classList.remove("is-open");
    launcher.setAttribute("aria-expanded", "false");
    launcher.focus();
  }

  launcher.addEventListener("click", function () {
    panel.hidden ? open() : shut();
  });
  close.addEventListener("click", shut);
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && !panel.hidden) shut();
  });
  form.addEventListener("submit", function (e) {
    e.preventDefault();
    ask(input.value);
  });

  // Sivuston omat "Kysy chatissa" -linkit (href="#chat") avaavat chatin.
  document.addEventListener("click", function (e) {
    var a = e.target.closest && e.target.closest('a[href="#chat"]');
    if (a) {
      e.preventDefault();
      open();
    }
  });
})();
