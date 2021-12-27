/*
 * This file is part of StucksSeiten <https://stuck1a.de>,
 * Copyright (c) 2021.
 * StucksSeiten uses the StuckMVC framework, which is free software: you can
 * redistribute it and/or modify it under the terms of the GNU General Public
 * License version 3 as published by the Free Software Foundation.
 *
 * StucksSeiten is the official website of the StuckMVC framework and further
 * designed as an usage example. It is distributed in the hope that it will be useful,
 * but without any warranty; without even the implied warranty of merchantability
 * of fitness for a particular purpose. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * StucksSeiten. If not, see <https://www.gnu.org/licenses/>. 
 *
 * FOR A SOMEWHAT FREER WORLD.
 */

'use strict'

/*
 TODO [Optimierungen]:
   - Registrierung in findSidebarsInDOM() direkt über SIDEBAR_CLASS_LEFT und SIDEBAR_CLASS_RIGHT erledigen, damit man nicht extra dafür
     die SIDEBAR_CLASS benötigt bzw der Nutzer diese auch noch zuweisen muss im Template.
*/



/* ###################################################################### */
/* #############################   SETTINGS   ########################### */
/* ###################################################################### */

const SIDEBAR_CLASS = 'sidebar';              // name of css class to register DOM elements as sidebar container
const SIDEBAR_CLASS_LEFT = 'sidebar-left';    // name of css class to specify a sidebar as left-sided (default)
const SIDEBAR_CLASS_RIGHT = 'sidebar-right';  // name of css class to specify a sidebar as right-sided

const SIDEBAR_ZINDEX = '20000';               // z-index value for sidebars (overlay will use it, too, reduced by one)
const SIDEBAR_HEIGHT = '102vh';               // height of sidebars (+2% overflow to hide d-fixed shake on touch scroll bug)
const SIDEBAR_WIDTH = '80%';                  // target width of sidebars
const SIDEBAR_MAXWIDTH = '400px';             // upper limit for width of sidebars
const SIDEBAR_TOGGLE_DURATION = 300;          // duration to fade a sidebar in/out [milliseconds!]

const OVERLAY_PARENT = 'sidebars';            // Element in which to append the overlay (uses first matching id/tag/class)
const OVERLAY_OPACITY = '50%';                // strength of the darkening effect of overlay elements




/* ###################################################################### */
/* ##########################   PUBLIC MEMBERS   ######################## */
/* ###################################################################### */

/**
 * Opens/Closes target sidebar.
 *
 * @param {string} id  ID of the target sidebar (from its HTML element)
 */
function toggle_sidebar(id) {
  // ensure script is ready, initialize if not
  if ( !registeredSidebars ) {
    registeredSidebars = registerSidebarsFromDOM();
  }
  // find and toggle target sidebar
  const sidebar = document.getElementById(id);
  if ( sidebar !== undefined ) {
    isOpen(sidebar) ? closeSidebar(sidebar) : openSidebar(sidebar);
  }
}




/* ###################################################################### */
/* #########################   PRIVATE MEMBERS   ######################## */
/* ###################################################################### */

/**
 * Checks whether a sidebar is currently opened or not.
 *
 * @param {HTMLElement} sidebar  Target sidebar
 *
 * @return {boolean}  True if the sidebars is opened, false otherwise
 */
function isOpen(sidebar) {
  return sidebar.getAttribute("state") === 'open';
}


/**
 * Fetches elements from DOM which use SIDEBAR_CLASS and assigns mandatory sidebar style rules.
 *
 * @return HTMLCollectionOf<Element>  Collection of references to all sidebar elements in DOM
 */
function registerSidebarsFromDOM() {
  let initLeft;
  let alignment;
  let sidebars = document.getElementsByClassName(SIDEBAR_CLASS);
  for ( /** @var {HTMLElement} */ let sidebar of sidebars ) {
    // general
    sidebar.setAttribute('state', 'close');
    sidebar.style.visibility = 'hidden';
    sidebar.style.position = 'fixed';
    sidebar.style.top = '0px';
    sidebar.style.zIndex = SIDEBAR_ZINDEX;
    sidebar.style.height = SIDEBAR_HEIGHT;
    sidebar.style.maxWidth = SIDEBAR_MAXWIDTH;
    sidebar.style.width = SIDEBAR_WIDTH;
    sidebar.style.transition = 'left ' + SIDEBAR_TOGGLE_DURATION + 'ms ease';
    // side-specific
    if ( sidebar.classList.contains(SIDEBAR_CLASS_RIGHT) ) {
      alignment = 'right';
      initLeft = '100vw';
    } else {
      alignment = 'left';
      initLeft = '-' + parseInt(window.getComputedStyle(sidebar).width) + 'px';
    }
    sidebar.style.left = initLeft;
    sidebar.setAttribute('alignment', alignment);
  }
  return sidebars;
}


/**
 * Closes a sidebar.
 *
 * @param {HTMLElement} sidebar  Reference to target sidebars DOM element
 */
function closeSidebar(sidebar) {
  const hide = function(sidebar) { sidebar.style.visibility = 'hidden'; };
  getOverlayElement().style.display = 'none';
  let sidebarLeft = parseInt(window.getComputedStyle(sidebar).left);
  let sidebarWidth = parseInt(window.getComputedStyle(sidebar).width);
  // invert direction of the close movement for right-sided sidebars
  if ( sidebar.getAttribute('alignment') === 'right' ) {
    sidebarWidth = -1 * sidebarWidth;
  }
  sidebar.style.left = sidebarLeft - sidebarWidth + 'px';
  setTimeout(function() { hide(sidebar); }, SIDEBAR_TOGGLE_DURATION);
  sidebar.setAttribute('state', 'close');
  currentlyOpen = undefined;
}


/**
 * Opens a sidebar.
 *
 * @param {HTMLElement} sidebar  Reference to the DOM element of the target sidebar
 */
function openSidebar(sidebar) {
  getOverlayElement().style.display = 'block';
  sidebar.style.visibility = 'visible';
  sidebar.setAttribute('state', 'open');
  currentlyOpen = sidebar;
  // left-sided
  if ( sidebar.getAttribute('alignment') === 'left' ) {
    sidebar.style.left = '0px';
    return;
  }
  // right-sided
  sidebar.style.left = parseInt(window.getComputedStyle(sidebar).left) - parseInt(window.getComputedStyle(sidebar).width) + 'px';
}


/**
 * Returns the shared overlay element. If none exists yet, a new one will be build.
 * The overlay is located some layers behind the sidebars and darkens the background.
 * It will close sidebars on click.
 *
 * @return {HTMLDivElement}  Reference to the shared overlay element object
 */
function getOverlayElement() {
  if ( !overlayElement ) {
    // generate and adjust the overlay element
    var overlay = document.createElement('div');
    overlay.style.position = 'fixed';
    overlay.style.backgroundColor = '#000';
    overlay.style.left = '0px';
    overlay.style.top = '0px';
    overlay.style.width = '100vw';
    overlay.style.height = '102vh';  // +2% to hide d-fixed shake bug on touch scroll
    overlay.style.zIndex = (parseInt(SIDEBAR_ZINDEX) - 10).toString();
    overlay.style.opacity = OVERLAY_OPACITY;
    // close any sidebar on click
    overlay.addEventListener('click', function() {
      for ( let currentSidebar of registeredSidebars ) {
        if ( currentSidebar.getAttribute('state') === 'open' ) {
          closeSidebar(currentSidebar);
        }
      }
      this.style.display = 'none';
    });
    // find daddy (search order: id -> tag -> class -> body -> rootNode)
    let target = OVERLAY_PARENT === '' ? 'body' : OVERLAY_PARENT;
    let parent = document.getElementById(target);
    if ( parent === undefined || parent === null ) {
      parent = document.getElementsByTagName(target)[0];
      if ( parent === undefined || parent === null ) {
        parent = document.getElementsByClassName(target)[0];
        if ( parent === undefined || parent === null ) {
          parent = document.body;
          if ( parent === undefined || parent === null ) {
          parent = document.getRootNode().firstChild;
          }
        }
      }
    }
    parent.appendChild(overlay);
    overlayElement = overlay;
  }
  return overlayElement;
}



/* ###################################################################### */
/* ##########################   GLOBAL SCRIPT   ######################### */
/* ###################################################################### */

let registeredSidebars;
let overlayElement;
let currentlyOpen;


// register and set up sidebars
window.onload = function() {
  registeredSidebars = registerSidebarsFromDOM();
};

// close any opened sidebar and correct scaling bias
window.onresize = function() {
  if ( currentlyOpen ) {
    currentlyOpen.style.visibility = 'hidden';
    closeSidebar(currentlyOpen);
  }
  if ( registeredSidebars ) {
    for ( let sidebar of registeredSidebars ) {
      if ( sidebar.getAttribute('alignment') === 'right' ) {
        sidebar.style.left = '100vw';
      }
    }
  }
}
