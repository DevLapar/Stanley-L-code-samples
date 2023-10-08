/**
 * Author: Stanley
 * Company: Aloha Shaka (www.alohashaka.com)
 * JS script that creates a horizontal scrolling effect that loads specific elements based on window orientation
 * This script makes use of AlohaCore that is used to combat duplicate code by providing reusable functions in one place
 * Live demonstration: www.stanley.fyi/#slp-projects
 */



/** Init required HTMLElements*/
const timeline = document.querySelector('.sl-timeline');
const timelineWrapper = document.querySelector('.sl-timeline-bar');
const timelineDatesHolder = document.querySelector('.sl-timeline-dates-holder');
const timelineDates = timelineDatesHolder.querySelector('.sl-timeline-dates');
const tldPos = asCore.getBoundingClientRect(timelineDates);
const endlessPageRef = document.getElementById('slp-projects');

/** Init AlohaCarousel which creates a horizontal carousel effect*/
const asCarousel = new AlohaCarousel(null);

/** Init EndlessScroll which creates a horizontal scrolling effect*/
const tles = new EndlessScroll(endlessPageRef, horizontalScroll);

const esPos = asCore.getBoundingClientRect(tles.endlessScroller);
const projectInitTime = 600;
const projectQuickInitTime = 300;

const quickInits = [
	'slp-intro',
	'slp-funfact'
]

let scrollTimer = null;
let eleLocations = null;
let activeElement = null;

/** On first load init element locations*/
asCore.onLoad(initLocations);

/** Reset some functionality on window resize*/
asCore.addEvent(window, 'resize', () => asCore.debounce(() => {
	initLocations();
	horizontalScroll();
}, 100));



/** Function that initializes element locations*/
function initLocations() {
	timelineDates.style.width = tles.endlessScroller.scrollWidth + 'px';
	eleLocations = determineLocations();
}


/** Function that determines element locations*/
function determineLocations() {
	const projects = document.getElementsByClassName('sl-project-holder');
	this.removeConnectionPoints();


	/** Create an array based on the querySelector of HTMLElements*/
	return Array.from(projects).map(projectEle => {
		const trigger = projectEle.querySelector('.slp-receiver');
		const triggerLoc = asCore.getBoundingClientRect(trigger);
		const accessLoc = Object.assign({}, triggerLoc);


		/** Create an HTMLElement in the dynamic timeline based on the location of current element*/
		accessLoc.x = (esPos.x + triggerLoc.x) - tldPos.x;
		const accessPoint = createConnectPoint(accessLoc, projectEle);


		/** Sets a trigger point on which certain JS function will react*/
		accessLoc.x += (triggerLoc.width / 2);

		const triggerData = {
			location: accessLoc,
			access: accessPoint,
			element: trigger
		};


		const eleData = {
			location: asCore.getBoundingClientRect(projectEle),
			element: projectEle,
			trigger: triggerData,
			eventTO: null
		};

		return eleData;
	});
}


/** Function removes a connection point from the DOM*/
function removeConnectionPoints() {
	const points = Array.from(timelineDates.querySelectorAll('.slt-connector-holder'));
	points.forEach(p => {
		p.remove();
	});
}


/** Create an HTMLElement in the dynamic timeline based on the location of current element*/
function createConnectPoint(location, parentEle) {
	const sltConnectorHolder = document.createElement('div');
	sltConnectorHolder.classList.add('slt-connector-holder');
	sltConnectorHolder.style.left = location.x + 'px';

	const sltConnector = document.createElement('div');
	sltConnector.classList.add('slt-connector');
	sltConnectorHolder.appendChild(sltConnector);
	if (parentEle) {
		const projectIntro = parentEle.querySelector('.slp-timeline-intro');
		if (projectIntro) sltConnectorHolder.appendChild(projectIntro.cloneNode(true));
	}

	timelineDates.appendChild(sltConnectorHolder);
	return sltConnectorHolder;
}

/** Function that calls when horizontal scrolling*/
function horizontalScroll() {
	if (scrollTimer !== null) clearTimeout(scrollTimer);

	if (tles.endlessScroller) {
		/** Shifts the scrollLeft (horizontallu) based on the wrapper scrollTop position (vertically) */
		timelineDatesHolder.scrollLeft = tles.endlessWrapper.offsetTop;

		const hasHorizontalScrollbar = tles.scrollWidth > tles.docWidth;
		if (hasHorizontalScrollbar) {

			/** Determines the actual scrollable content */
			const actualScrollableWidth = tles.endlessScroller.scrollWidth - tles.endlessScroller.offsetWidth;

			if (!timeline.classList.contains('move')) timeline.classList.add('move');
			scrollTimer = setTimeout(() => timeline.classList.remove('move'), 300);


			/** 
			 * A calculation that determines the scroll position of scroll container
			 * relative to its parent wrapper. Based on this calculation the dynamic timeline may
			 * initiate a functionality for position based elements
			 */
			const scrollPerc = tles.endlessScroller.scrollLeft / actualScrollableWidth;
			const tlWidthOffRatio = tles.timelineContainer ? (tles.scrollWidth - (tldPos.x * 2)) / (tles.timelineContainer.offsetWidth) : 1;
			const timelineProg = scrollPerc * tles.timelineContainer.offsetWidth;
			const triggerPoint = timelineProg * tlWidthOffRatio;

			/** Initiate a functionality for position based elements */
			checkActivation(triggerPoint);
			timeline.style.width = timelineProg + 'px';

		}
	}
}

/** Checks whether elements should react to the provided triggerpoint */
function checkActivation(check) {
	if (eleLocations && eleLocations.length > 0) {
		for (let index = 0; index < eleLocations.length; index++) {
			const eleData = eleLocations[index];
			const trigger = eleData.trigger;

			/** 
			 * trigger.location.x = the trigger point of current element 
			 * based on check activate or deactivate element
			 */
			if (trigger.location.x <= check) {
				activateElement(eleData.element, true, eleData);
				activateElement(eleData.trigger.access, false, eleData);
			} else {
				deActivateElement(eleData.element, true, eleData);
				deActivateElement(eleData.trigger.access, false, eleData);
				break;
			}
		}
	}
}

/** Sets an element active, mainly for visual purposes */
function setActiveElement(parent, deactivate = false) {
	if (activeElement) {
		activeElement.element.classList.remove('active');
		activeElement.element.classList.remove('scroll-right');
		activeElement.element.classList.remove('scroll-left');
	}

	activeElement = parent;

	if (activeElement) {
		if (tles.scrollDirection === 'right') activeElement.element.classList.add('scroll-right');
		else activeElement.element.classList.add('scroll-left');
		activeElement.element.classList.add('active');
	}
}

/** Activates an element and performs related functionality */
function activateElement(element, carouselCheck = false, parent) {
	if (!element.classList.contains('activated')) {
		element.classList.add('activated');
		if (carouselCheck) {

			/** Sets an element active, mainly for visual purposes */
			setActiveElement(parent);


			if (parent.eventTO !== null) {
				clearTimeout(parent.eventTO);
				removeInitStatus(element);
			}

			/** Sets an element inited, mainly for visual purposes */
			const initTime = isQuickInit(element) ? projectQuickInitTime : projectInitTime;
			parent.eventTO = setTimeout(() => addInitStatus(element, initProject), initTime);


			/** Inits related carousel of current element (images that start scrolling) */
			const carousel = element.querySelector('.aloha-carousel');
			asCarousel.initCarousel(carousel);
		}
	}
}

/** Checks if element is quick init, ie. should act immediately */
function isQuickInit(element) {
	for (let index = 0; index < quickInits.length; index++) {
		const qiClass = quickInits[index];
		if (element.classList.contains(qiClass)) return true;
	}
	return false;
}

/** Inits projects related to current (active) element */
function initProject(element) {
	infoDisplay(undefined, undefined, element);
}

/** Deinits projects related to current (active) element */
function deInitProject(element) {
	infoDisplay(undefined, -1, element);
}

/** Inits element and performs related functionality */
function addInitStatus(element, callback) {
	element.classList.add('inited');
	if (callback) {
		callback.apply(this, arguments);
		callback();
	}
}

/** De-inits element and performs related functionality */
function removeInitStatus(element, callback) {
	element.classList.remove('inited');
	if (callback) {
		callback.apply(this, arguments);
		callback();
	}
}

/** Deactivates an element and performs related functionality */
function deActivateElement(element, carouselCheck = false, parent) {
	if (element.classList.contains('activated')) {
		element.classList.remove('activated');
		if (carouselCheck) {
			clearTimeout(parent.eventTO);
			const lastEle = lastActivatedElement();
			setActiveElement(lastEle, true);

			/** Deinits element */
			removeInitStatus(element, deInitProject);

			/** De-inits related carousel of current element (images that stop scrolling) */
			const carousel = element.querySelector('.aloha-carousel');
			asCarousel.destroyCarousel(carousel);
		}
	}
}

/** Gets last activated element that was active before current active element */
function lastActivatedElement() {
	let i = eleLocations.length - 1;
	for (; i >= 0; i--) {
		if (eleLocations[i].element.classList.contains('activated')) return eleLocations[i];
	}
	return null;
}

/** Toggles related element Description panel */
function displayDescription(eleId, hostEle = null) {
	const parentEle = hostEle ? hostEle : document.getElementById(eleId);
	if (parentEle) {
		const descriptionsHolder = parentEle.querySelector('.slpi-description-holder');

		if (descriptionsHolder) {
			const descs = Array.from(descriptionsHolder.querySelectorAll('.slpi-description'));

			if (descs && descs.length > 0) {
				let currentActiveIx = null;
				descs.forEach((element, i) => {
					if (currentActiveIx === null && !element.classList.contains('inactive')) currentActiveIx = i;
					element.classList.add('inactive');
				});

				const nextIx = currentActiveIx < (descs.length - 1) ? currentActiveIx + 1 : 0;
				if (nextIx > -1 && typeof descs[nextIx] !== 'undefined') descs[nextIx].classList.remove('inactive');
			}
		}
	}
}

/** Toggles related element Info panel */
function infoDisplay(eleId, index = 0, hostEle = null) {
	const parentEle = hostEle ? hostEle : document.getElementById(eleId);
	if (parentEle) {
		const eleNav = parentEle.querySelector('.slpi-navigation-holder');
		const infoHolder = parentEle.querySelector('.sl-project-info');

		if (eleNav && infoHolder) {
			const eleNavItems = Array.from(eleNav.querySelectorAll('.slpin-item-holder'));
			const infoEntries = Array.from(infoHolder.querySelectorAll('.slpi-content'));

			if (eleNavItems && eleNavItems.length > 0) {
				eleNavItems.forEach(element => {
					element.classList.remove('active');
				});
				if (index > -1) eleNavItems[index].classList.add('active');
			}

			if (infoEntries && infoEntries.length > 0) {
				infoEntries.forEach(element => {
					element.classList.remove('active');
				});
				if (index > -1 && typeof infoEntries[index] !== 'undefined') infoEntries[index].classList.add('active');
			}
		}
	}
}