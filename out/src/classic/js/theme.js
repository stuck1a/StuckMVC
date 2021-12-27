/**
 * Ändert Beschriftung von Donate-Buttons in Danksagung.
 */
function sayThanks() {
  $(".btn-donate").text('Danke :)');
}


/**
 * Blendet das PopUp-Menü ein/aus.
 */
function togglePopupMenu() {
  alert("togglePopupMenu getriggert")
}


/**
 * Blendet die zweite Reihe (Navigationsleiste) ein/aus.
 */
function toggleNavbar() {
  alert("toggleNavbar getriggert");
}




/*
 * #################################################################################
 * ##############################    STARTUP SKRIPTE    ############################
 * #################################################################################
 */

// EventListener registrieren
$('.triggerPopupMenu').on('click', togglePopupMenu);
$('.triggerNavbar').on('click', toggleNavbar);
$('.btn-donate').on('click', sayThanks);


$(document).ready (function() {
  // startup tasks
});




