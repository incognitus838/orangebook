(function () {
  var burger = document.querySelector(".lp-burger");
  var overlay = document.querySelector(".lp-overlay");

  function setMenu(open) {
    document.body.classList.toggle("lp-menu-open", open);
    if (burger) {
      burger.setAttribute("aria-expanded", open ? "true" : "false");
      burger.setAttribute("aria-label", open ? "Close menu" : "Open menu");
    }
  }

  if (burger && overlay) {
    burger.addEventListener("click", function () {
      setMenu(!document.body.classList.contains("lp-menu-open"));
    });
    overlay.querySelectorAll("a").forEach(function (link) {
      link.addEventListener("click", function () {
        setMenu(false);
      });
    });
    overlay.addEventListener("click", function (event) {
      if (event.target === overlay) setMenu(false);
    });
    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") setMenu(false);
    });
  }

  document.querySelectorAll(".lp-form").forEach(function (form) {
    form.setAttribute("novalidate", "novalidate");
    form.addEventListener("submit", function (event) {
      var ok = true;
      form.querySelectorAll("[required]").forEach(function (field) {
        var err = field.nextElementSibling;
        if (!err || !err.classList.contains("lp-field-err")) {
          err = document.createElement("p");
          err.className = "lp-field-err";
          field.insertAdjacentElement("afterend", err);
        }
        if (!String(field.value || "").trim()) {
          err.textContent = "This field is required.";
          ok = false;
        } else if (field.type === "email" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value)) {
          err.textContent = "Enter a valid email.";
          ok = false;
        } else if (err.textContent) {
          err.textContent = "";
        }
      });
      if (!ok) event.preventDefault();
    });
  });
})();
