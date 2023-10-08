/**
 * Author: Stanley
 * Company: Aloha Shaka (www.alohashaka.com)
 * EndlessScroll is an utility class that transforms a vertical scrollable page into a horizontal scrollable page 
 * Live demonstration: www.stanley.fyi/#slp-about
 */

/** Init required HTMLElements*/
const abtPage = document.querySelector('.slp-about');
const abtContentOverlay = abtPage.querySelector('.slpa-content-overlay');
const abtSomethingsEle = abtPage.querySelector('.slpa-somethings');
const abtSomethings = Array.from(abtSomethingsEle.querySelectorAll('.slpas-something'));
const abtCategories = Array.from(abtSomethingsEle.querySelectorAll('.slpas-category'));
const abtSomethingsProjector = abtPage.querySelector('.slpac-things');
const abtProgressProjector = abtPage.querySelector('.slpac-progress');
const abtAmIEle = abtPage.querySelector('.slpac-ami');
const abtAboutMeHolder = abtPage.querySelector('.slpac-about');
const abtAboutMeCorners = abtPage.querySelector('.slpaca-corners-holder');
const abtStanleySVG = document.getElementById('stanley-svg-obj');

/** Init new StickScroll class with related HTMLelements
 * Also defines the truthy functions and non-truthy functions
*/
const abtStickyScroll = new StickyScroll(abtContentOverlay, abtSomethingsEle, abtPage);
abtStickyScroll.setScrollFns(abtStickyDo, abtNostickyDo);

let repeatData;

/** Function that executes when truthy */
function abtStickyDo() {
	abtPage.classList.add('inited');

	/** If is active page animate/draw related SVG */
	if (asCore.activePage === abtPage) asCore.drawSVG(abtStanleySVG);

	for (let index = 0; index < abtCategories.length; index++) {
		/** Based on current page scroll position determine the currently active element */
		if (returnItem = abtStickyScroll.determineActiveEle(abtCategories, index, abtStickyScroll.stateVars.category)) {
			abtStickyScroll.stateVars.category = returnItem;
			abtStickyScroll.stateVars.categoryIx = index;

			const iAmEle = abtStickyScroll.stateVars.category.querySelector('.slpas-iam');
			abtStickyScroll.stateVars.category_abouts = Array.from(abtStickyScroll.stateVars.category.querySelectorAll('.slpas-something'));
			if (iAmEle) {
				abtAmIEle.innerHTML = '';

				/** Clear a repeater if it already exists */
				if (repeatData) asCore.clearRepeat(repeatData.id);

				const textWrapper = document.createElement('div');

				/** Apply all styles of currently active element to the dynamic element */
				textWrapper.classList.add(...Array.from(abtStickyScroll.stateVars.category.classList));
				abtAmIEle.appendChild(textWrapper);

				/** Created a new repeater that defines an repeater of 75 milisecs */
				repeatData = asCore.addRepeat(75);

				/** For each letter in provided string write it to the element */
				for (let j = 0; j < iAmEle.innerText.length; j++) {
					const amILetter = iAmEle.innerText[j];

					/** Repeat execution based on repeatData definitions */
					asCore.repeat(repeatData, j, () => textWrapper.innerHTML += amILetter);
				}
			}
			break;
		}
	}

	/** Iterate over about elements and determine the currently active element */
	for (let index = 0; index < abtSomethings.length; index++) {
		if (returnItem = abtStickyScroll.determineActiveEle(abtSomethings, index, abtStickyScroll.stateVars.thing)) {

			/** Clear the inner HTML of projector */
			abtSomethingsProjector.innerHTML = '';
			abtStickyScroll.stateVars.thing = returnItem;

			const textWrapper = document.createElement('div');
			textWrapper.classList.add('slpact-thing');

			/** Adds the up class, mainly for animation purposes that execute on display*/
			if (abtStickyScroll.scrollDirection === 'up') textWrapper.classList.add('up');
			textWrapper.innerHTML = abtStickyScroll.stateVars.thing.innerHTML;
			abtSomethingsProjector.appendChild(textWrapper);


			/** Counter of current index out of total amount of amount elements*/
			abtProgressProjector.innerHTML = (index + 1) + '/' + abtSomethings.length;

			/** Animmate corners based on the width of given element */
			const aboutMeRect = abtAboutMeHolder.getBoundingClientRect();
			abtAboutMeCorners.classList.add('inited');
			abtAboutMeCorners.style.height = abtAboutMeHolder.offsetHeight + 'px';
			abtAboutMeCorners.style.width = abtAboutMeHolder.offsetWidth + 'px';
			abtAboutMeCorners.style.top = aboutMeRect.top + 'px';
			abtAboutMeCorners.style.left = aboutMeRect.left + 'px';

			break;
		}
	}
}

/** Function that executes when falsy */
function abtNostickyDo() {
	abtPage.classList.remove('inited');
	asCore.resetSVGs(abtPage);
}
