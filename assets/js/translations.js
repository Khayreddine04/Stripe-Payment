/*********************** Start Translations ******************************/

// Get Language of User
function getLanguage() {
  let urlParams = new URLSearchParams(window.location.search);
  let langParam = urlParams.get("lang");

  let supportedLangs = [
    "af", // Afrikaans
    "ar", // Arabic
    "be", // Belarusian
    "bg", // Bulgarian
    "bs", // Bosnian
    "br", // brazil
    "ca", // Catalan
    "cs", // Czech
    "da", // Danish
    "de", // German
    "el", // Greek
    "en", // English
    "es", // Spanish
    "et", // Estonian
    "fi", // Finnish
    "fo", // Faroese
    "fr", // French
    "fy", // Frisian
    "ga", // Irish
    "gl", // Galician
    "he", // Hebrew
    "hr", // Croatian
    "hsb", // Upper Sorbian
    "hu", // Hungarian
    "hy", // Armenian
    "is", // Icelandic
    "it", // Italian
    "ja", // Japanese
    "kl", // Greenlandic
    "lb", // Luxembourgish
    "lt", // Lithuanian
    "lv", // Latvian
    "me", // Montenegrin (Latin script)
    "mk", // Macedonian
    "nl", // Dutch
    "no", // Norwegian
    "oc", // Occitan
    "pl", // Polish
    "pt", // Portuguese
    "ro", // Romanian
    "ru", // Russian
    "si", // Sinhala
    "sk", // Slovak
    "sl", // Slovenian
    "sq", // Albanian
    "sr", // Serbian (Cyrillic script)
    "sv", // Swedish
    "ta", // Tamil
    "tr", // Turkish
    "uk", // Ukrainian
    "uz", // Uzbek
    "yi", // Yiddish
    "zh", // Chinese Simplified
  ];

  // If 'lang' param exists in URL, use it
  if (langParam && supportedLangs.includes(langParam.toLowerCase())) {
    return langParam.toLowerCase();
  }

  // Otherwise, use browser language
  let userLang = navigator.language.slice(0, 2).toLowerCase();
  return supportedLangs.includes(userLang) ? userLang : "en";
}

const lang = getLanguage(); // Get the language dynamically

// Set cookie for PHP to detect language on next request
document.cookie = "site_lang=" + lang + "; path=/; max-age=2592000";

const windowLang = window.lang;
const templateName = window.currentTheme;

// Initialize siteTranslations early to prevent undefined errors
window.siteTranslations = window.siteTranslations || {};

// Helper function to safely get translation with fallback
window.getTranslation = function (key, fallback = "") {
  return (
    (window.siteTranslations && window.siteTranslations[key]) || fallback || key
  );
};

console.log("Language :", lang, "-------", "Design : ", templateName);

document.addEventListener("DOMContentLoaded", () => {
  if (
    lang == "ar" ||
    lang == "he" ||
    lang == "fa" ||
    lang == "ur" ||
    lang == "yi" ||
    lang == "syc" ||
    lang == "ps" ||
    lang == "ckb" ||
    lang == "dv" ||
    lang == "arc"
  ) {
    // Set document direction
    document.body.dir = "rtl";

    // For pseudo-elements, you need to add a CSS class or modify existing styles
    const style = document.createElement("style");
    style.textContent = `
    .product-info::before {
        left: auto !important;
        right: 0 !important;
    }
    input#phone {
        text-align: end !important;
    }
    .progressbar:before {
        left: 26% !important;
    }
`;
    document.head.appendChild(style);

    // Update padding - remove auto and use proper values
    const productInfo = document.querySelector(".product-info");
    if (productInfo) {
      productInfo.style.paddingLeft = "0";
      productInfo.style.paddingRight = "24px";
    }
  }
});

async function loadTranslations() {
  try {
    let response = await fetch(
      `./templates/form/${templateName}/translations/${lang}.json`
    ); // Fetch translation JSON

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    let translations = await response.json(); // Parse JSON
    window.siteTranslations = translations; // Expose translations globally

    console.log(
      "Path : ",
      `./templates/form/${templateName}/translations/${lang}.json`
    );
    console.log("Translations : ", translations);

    // Apply translations to elements with data-i18n
    document.querySelectorAll("[data-i18n]").forEach((el) => {
      let key = el.getAttribute("data-i18n");
      if (translations[key]) {
        el.innerHTML = translations[key];
      }
    });

    // Apply translations to input placeholders with data-i18n-placeholder
    document.querySelectorAll("[data-i18n-placeholder]").forEach((el) => {
      let key = el.getAttribute("data-i18n-placeholder");
      if (translations[key]) {
        el.placeholder = translations[key];
      }
    });

    console.log("Translations loaded");

    // Dispatch custom event to notify that translations are loaded
    window.dispatchEvent(
      new CustomEvent("translationsLoaded", {
        detail: { translations: translations },
      })
    );

    // Ensure URL parameters apply AFTER translations
  } catch (error) {
    console.error("Error loading translations:", error);
    console.error(
      "Failed to load:",
      `./templates/form/${templateName}/translations/${lang}.json`
    );
    // Still dispatch the event even if loading failed
    window.dispatchEvent(
      new CustomEvent("translationsLoaded", {
        detail: { translations: {}, error: error },
      })
    );
  }
}

// Ensure review content loads after translations
async function initializePage() {
  await loadTranslations(); // Load translations first
}

initializePage();
window.addEventListener("DOMContentLoaded", () => {
  initializePage();
});
