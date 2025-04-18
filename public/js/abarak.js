/***************************************/
/* Gestion des cookies et de la langue */
/***************************************/

async function switchLanguage(lang) {
    let translationFile = lang + ".json";
    var page = document.URL.split('/').pop();
    if (page == '') { page = "home"; }

    try {
        fetch("./translations/" + translationFile)
            .then((responseJson) => responseJson.json())
            .then((data) => {
                for (const translation of data[page]) {
                    const element = document.getElementById(translation.element);
                    if (translation.element == "placeholder") {
                        switchPlaceholder(translation.text);
                    } else if (Array.isArray(translation.text)) {
                        let indexElements = 0;
                        for (let index = 0; index < translation.text.length; index++) {
                            const subtext = translation.text[index];
                            const subelement = element.children[indexElements];

                            if (subelement.children.length > 1) {
                                subelement.children[0].textContent = subtext;
                                subelement.children[1].textContent = translation.text[++index];
                            } else if (subelement.children.length == 1) {
                                let childrens = [];
                                for (const children of subelement.children) {
                                    childrens.push(children);
                                }
                                subelement.textContent = subtext;
                                for (const children of childrens) {
                                    subelement.appendChild(children);
                                }
                            }
                            else if (subelement.tagName == "INPUT" && subelement.getAttribute('type') == 'submit') {
                                subelement.value = subtext;
                            }
                            else {
                                subelement.textContent = subtext;
                            }
                            indexElements++;
                        }
                    } else {
                        element.textContent = translation.text;
                    }
                }
            });
        document.getElementById(lang).setAttribute("selected", "");
        setCookie("lang", lang, 360);
        if (page == "about") { switchCalendlyLang(lang); }
    } catch (error) {
        console.log(error.message);
    }

    // Envoi de l'événement de changement de langue
    sendTrackingEvent("language_change", { language: lang });
}

const selectLanguage = document.getElementById("language");
selectLanguage.addEventListener("change", function (event) {
    let lang = event.target.value;
    switchLanguage(lang);
    window.location.reload();
});

function setCookie(cookieName, cookieValue, expireDays) {
    const d = new Date();
    d.setTime(d.getTime() + (expireDays * 24 * 60 * 60 * 1000));
    let expires = "expires=" + d.toUTCString();
    document.cookie = cookieName + "=" + cookieValue + ";" + expires + ";SameSite=Strict;path=/; Secure;";
}

function getCookie(cookieName) {
    let name = cookieName + "=";
    let ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

function checkLangCookie() {
    let lang = getCookie("lang");

    if (lang != "") {
        switchLanguage(lang);
    } else {
        let langDefault = "en";
        for (const langOption of selectLanguage.children) {
            if (navigator.language == langOption.getAttribute("id")) {
                langDefault = langOption.getAttribute("id");
            }
        }
        console.log(langDefault);

        setCookie("lang", langDefault, 365);
        switchLanguage(langDefault);
    }
}

/* Fonctions animations */

function addDelayAnimation(element, time) {
    if (element == null || time == null || element.style == '' || time < 0) { return; }
    setTimeout(() => {
        element.style.animation = 'fadeIn 2s forwards';
    }, time);
}

/* Fonctions de Tracking */

// Récupère ou crée un identifiant unique pour le visiteur (via cookie)
function getVisitorId() {
    let visitor = getCookie("visitor_id");
    if (visitor == "") {
        visitor = "v" + Date.now() + Math.floor(Math.random() * 10000);
        setCookie("visitor_id", visitor, 365);
    }
    return visitor;
}

// Envoi d'un événement de tracking au serveur
document.getElementById("search-form").addEventListener("submit", function(e) {
    let query = document.getElementById("search-input").value;
    let data = {
        event: "search",
        query: query,
        visitor_id: getVisitorId(),
        timestamp: new Date().toISOString()
    };
    let blob = new Blob([JSON.stringify(data)], {type: "application/json"});
    if (navigator.sendBeacon) {
        navigator.sendBeacon("tracker.php", blob);
    } else {
        let xhr = new XMLHttpRequest();
        xhr.open("POST", "tracker.php", false);
        xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
        xhr.send(JSON.stringify(data));
    }
});

function switchPlaceholder(text) {
    const input = document.getElementById('search-input');
    if (!input) return;
    input.placeholder = text || "Search...";
}

document.addEventListener('DOMContentLoaded', function() {
    const engineButtons = document.querySelectorAll('.engine-button');
    const searchForm = document.getElementById('search-form');
    const searchInput = document.getElementById('search-input');
    const suggestionsList = document.getElementById('suggestions');
    let selectedEngine = 'google';
  
    engineButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        engineButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        selectedEngine = btn.getAttribute('data-engine');
        if (selectedEngine === 'google') {
          searchForm.action = 'https://www.google.com/search';
        } else {
          searchForm.action = '/search'; // endpoint de démo Abarak
        }
        suggestionsList.innerHTML = '';
      });
    });
  
    const abarakStatic = [
      'Batterie de voiture',
      'Batterie de téléphone',
      'Batterie d’ordinateur',
      'Autres batteries',
      'Batterie (instrument de musique)',
      'Batterie (de cuisine)',
      'Carte à jouer',
      'Carte routière',
      'Carte à puce',
      'Autres cartes',
      'Carte postale',
      'Carte bristol',
      'Entrecôte'
    ];
  
    searchInput.addEventListener('input', function() {
      const query = this.value.trim();
      if (!query) {
        suggestionsList.innerHTML = '';
        return;
      }
      if (selectedEngine === 'google') {
        fetch('https://suggestqueries.google.com/complete/search?client=firefox&q=' + encodeURIComponent(query))
          .then(r => r.json())
          .then(data => updateSuggestions(data[1]))
          .catch(console.error);
      } else {
        const filtered = abarakStatic.filter(item => item.toLowerCase().startsWith(query.toLowerCase()));
        updateSuggestions(filtered);
      }
    });
  
    function updateSuggestions(items) {
      suggestionsList.innerHTML = '';
      items.slice(0, 5).forEach(text => {
        const opt = document.createElement('option');
        opt.value = text;
        suggestionsList.appendChild(opt);
      });
    }
  });
