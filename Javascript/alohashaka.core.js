/**
 * Author: Stanley
 * Company: Aloha Shaka (www.alohashaka.com)
 * AlohaShakaCore is an indepedent utility class that consists out of a collection of generic functions
 * this utility service is used to combat duplicate code by providing reusable functions in one place
 * Live demonstration: www.stanley.fyi/#slp-projects
 */
class AlohaShakaCore {
	_AS_screenSizes = [
		{ size: 'S', min: 0, max: 480 },
		{ size: 'M', min: 481, max: 768 },
		{ size: 'L', min: 769, max: 1200 },
		{ size: 'XL', min: 1201, max: -1 },
	];

	_AS_mobileSizes = ['s', 'm'];
	_AS_debounces = [];
	_AS_throttles = [];
	_AS_repeats = [];
	_AS_pages = [];

	_AS_screensize;
	_AS_wasMobile = false;
	_AS_prevCategory;
	_AS_deviceChanged = false;

	viewportHeight = window.innerHeight;
	viewportWidth = window.innerWidth;

	lastScrollTop;
	scrollDirection;
	scrollContainer = this.scrollContainer;

	activePage;
	lastActivePage;

	threshold = 5;

	/**On first init listen to events that determine some defaults or behaviors */
	constructor() {
		this.determineCategory();
		this.onLoad(() => {
			document.body.classList.add('inited');
			this.initPages();
			this.determineActivePage();
		});
		this.addEvent(window, 'resize', () => {
			this.determineCategory();
		});
	}

	/**Alias for onload event */
	onLoad(callback) {
		this.addEvent(window, 'load', () => { if (callback) callback(); });
	}

	/**Alias for onscroll event */
	onScroll(callback) {
		this.addEvent(this.scrollContainer, 'scroll', () => {
			if (callback) callback();
		});
	}

	/**Defines the scroll container on which it will listen to events */
	setScrollContainer(scrollContainer) {
		this.scrollContainer = scrollContainer;
		this.onScroll(() => {
			this.determineScrollDirection();
			this.determineActivePage()
		});
	}

	/**Defines related info regarding .page HTMLElements */
	initPages() {
		const pagesEles = document.querySelectorAll('.page');
		if (pagesEles) {
			const pages = Array.from(pagesEles);
			let totalHeight = 0;
			pages.forEach(page => {
				const pagePos = asCore.getBoundingClientRect(page);
				const pageData = {
					start_pos_x: totalHeight,
					element: page,
					pos: pagePos
				}
				this._AS_pages.push(pageData);
				totalHeight += pagePos.height;
			});
		}
	}

	/**Function to animately draw an SVG file */
	drawSVG(svgEle) {

		/**Define the contentDocument of given SVG element */
		const svgDoc = svgEle.contentDocument;
		const svgItems = svgDoc.querySelectorAll(".svgItem");
		const paths = svgItems.length;

		/**Execute if svgEle is not marked as loaded */
		if (!svgEle.classList.contains('loaded')) {

			/**Mark svgEle as loaded */
			svgEle.classList.add('loaded');


			/**For each path of svgEle */
			for (var i = 0; i < paths; i++) {
				const tSVG = svgItems[i];
				const pathlength = tSVG.getTotalLength();
				const oriColour = window.getComputedStyle(tSVG);
				const oriColourval = oriColour.getPropertyValue('fill');

				tSVG.style.transition = tSVG.style.WebkitTransition = 'none';
				if (!tSVG.classList.contains('staticColor')) {
					tSVG.style.fill = 'transparent';
				}

				/**Apply styles  */
				tSVG.style.strokeDasharray = pathlength + ' ' + pathlength;
				tSVG.style.strokeDashoffset = pathlength;
				tSVG.getBoundingClientRect();
				var animation_value = 'stroke-dashoffset 7s ease-in-out, fill 1s linear 3s, stroke-opacity 1s linear 5.5s';
				tSVG.style.transition = animation_value;
				tSVG.style.WebkitTransition = animation_value;
				tSVG.style.mozTransition = animation_value;
				tSVG.style.msTransition = animation_value;
				tSVG.style.oTransition = animation_value;
				tSVG.style.strokeDashoffset = '0';
				tSVG.style.strokeOpacity = '0';
				tSVG.style.fill = oriColourval;
			}
		}
	}

	/**Function to unload SVG files so that they can be drawn again */
	resetSVGs(page = null) {
		const objEles = page ? page.querySelectorAll('object') : document.querySelectorAll('document');
		const svgObjs = Array.from(objEles);
		if (svgObjs.length > 0) {
			svgObjs.forEach(svg => {
				this.resetSVG(svg);
			});
		}
	}

	/**Function to unload SVG file so that it can be drawn again */
	resetSVG(svgEle) {
		if (svgEle.classList.contains('loaded')) {
			svgEle.classList.remove('loaded');
			const svgDoc = svgEle.contentDocument;
			const svgItem = svgDoc ? svgDoc.querySelector(".svgItem") : null;
			if (svgItem) svgItem.removeAttribute('style');
		}
	}

	/**Function that determines the device size (window) category, eg small devices, medium or laptop/desktop sizes */
	determineCategory() {
		const windowWidth = window.innerWidth;
		const category = this._AS_screenSizes.find(c => {
			const max = c.max === -1 ? windowWidth : c.max;
			return windowWidth >= c.min && windowWidth <= max;
		});
		const categoryLow = category.size.toLowerCase();
		this._AS_deviceChanged = this._AS_prevCategory && this._AS_prevCategory !== categoryLow;
		this._AS_wasMobile = this.isConsideredMobile(this._AS_prevCategory) && this._AS_deviceChanged;
		if (typeof this._AS_prevCategory === 'undefined' || this._AS_deviceChanged) this._AS_prevCategory = categoryLow;
		this._AS_screensize = categoryLow;
	}

	/**Function that determines the direction the user scrolls to */
	determineScrollDirection() {
		const st = window.pageYOffset || this.scrollContainer.scrollTop;
		this.scrollDirection = (st > this.lastScrollTop) ? 'down' : 'up';
		this.lastScrollTop = st <= 0 ? 0 : st;
	}

	/**Function that determines which pages is currently active based on page location on scrollable container */
	determineActivePage() {
		if (this._AS_pages) {
			const pages = Array.from(this._AS_pages);
			for (const page of pages) {
				const pageEnd = page.start_pos_x + page.pos.height;
				if ((!this.activePage || (this.activePage !== page.element)) && this.scrollContainer.scrollTop >= page.start_pos_x && this.scrollContainer.scrollTop <= pageEnd) {
					if (this.activePage) {
						this.activePage.classList.remove('active');
						if (this.activePage !== page.element) this.lastActivePage = this.activePage;
					}
					page.element.classList.add('active');
					this.activePage = page.element;
					break;
				}
			}
		}
	}


	/**Alias to addEventListener also accounting for older browsers*/
	addEvent(obj, type, fn) {
		if (obj.addEventListener) obj.addEventListener(type, fn, false);
		else if (obj.attachEvent) obj.attachEvent('on' + type, () => fn.call(obj, window.event));
	}

	/**Alias to removeEventListener also accounting for older browsers */
	removeEvent(obj, type, fn) {
		if (obj.removeEventListener) obj.removeEventListener(type, fn, false);
		else if (obj.detachEvent) obj.detachEvent('on' + type, () => fn.call(obj, window.event));
	}

	deviceCategoryChanged() {
		return this._AS_deviceChanged;
	}

	getScreensizeCategory() {
		return this._AS_screensize;
	}

	wasScreensizeCategory() {
		return this._AS_prevCategory;
	}

	isConsideredMobile(category = this.getScreensizeCategory()) {
		return this._AS_mobileSizes.indexOf(category) > -1;
	}

	/**Get TransitionEnd event names accounting for different browser defaults */
	getTransitionEndEventName() {
		const transitions = {
			"transition": "transitionend",
			"OTransition": "oTransitionEnd",
			"MozTransition": "transitionend",
			"WebkitTransition": "webkitTransitionEnd"
		}
		const bodyStyle = document.body.style;
		for (let transition in transitions) {
			if (bodyStyle[transition] != undefined) {
				return transitions[transition];
			}
		}
	}

	/**Function that acts as a debouncer in which it await 
	 * until the user stopped interacting and then executes its callback */
	debounce(callback, time) {
		const debounceTimer = setTimeout(() => {
			callback();
			if (debounceTimer) clearTimeout(debounceTimer);
		}, time);
		this._AS_debounces.push(debounceTimer);
		return debounceTimer;
	}

	/**Function that acts as a throttler in which it executes every N miliseconds
	 * Expects throttleData in which is defined the behavior of the throttle
	*/
	throttle(throttleData, callback) {
		if (!throttleData || (throttleData && throttleData.pause)) return;

		throttleData.pause = true;
		throttleData.throttleTO = setTimeout(() => {
			callback();
			throttleData.pause = false;
			if (throttleData.throttleTO) clearTimeout(throttleData.throttleTO);
		}, throttleData.time);
	}

	/**Function that acts as a repeater and executes a callback as long as it is 
	 * been called every N miliseonds
	 * Expects repeatData in which is defined the behavior of the repeater
	*/
	repeat(repeatData, index, callback) {
		repeatData.busy = true;

		const repeatEntry = {
			index,
			callback,
			delay: index * repeatData.time,
			timeout: null
		};

		repeatEntry.timeout = setTimeout(() => {
			if (repeatEntry.callback) repeatEntry.callback();
			if (repeatEntry.timeout) clearTimeout(repeatEntry.timeout);
		}, repeatEntry.delay);

		repeatData.repeats.push(repeatEntry);
	}

	/**Function that defines the behavior of a new repeater */
	addRepeat(time) {
		const repeatData = {
			id: this.generateId(),
			time,
			repeats: []
		};
		this._AS_repeats.push(repeatData);
		return repeatData;
	}

	/**Function that clears and stops execution of a repeater */
	clearRepeat(id) {
		const repeatData = this.getRepeat(id);
		if (repeatData) {
			for (let index = 0; index < repeatData.repeats.length; index++) {
				const repeat = repeatData.repeats[index];
				const repeatTO = repeat.timeout;
				if (repeatTO) clearTimeout(repeatTO);
			}
			this._AS_repeats.splice(repeatData.ix, 1);
		}
	}

	/**Function that defines the behavior of a new throttle */
	addThrottle(time) {
		const throttleData = {
			id: this.generateId(),
			pause: false,
			time
		};
		this._AS_throttles.push(throttleData);
		return throttleData;
	};

	/**Function that clears and stops execution of a throttle */
	clearThrottle(id) {
		const throttleData = this.getThrottle(id);
		if (throttleData) {
			clearTimeout(throttleData.throttle);
			this._AS_throttles.splice(throttleData.ix, 1);
		}
	}

	getEntryIx(id, arr) {
		return arr.findIndex(t => t.id === id);
	}

	getEntryData(id, arr) {
		const entryIx = this.getEntryIx(id, arr);
		if (entryIx > -1) {
			arr[entryIx].ix = entryIx;
			return arr[entryIx];
		}
		return null;
	}

	getRepeat(id) {
		return this.getEntryData(id, this._AS_repeats);
	}

	getThrottle(id) {
		return this.getEntryData(id, this._AS_throttles);
	}

	/**Function to generate a unique ID based on a set of specifics */
	generateId(idLength = 20) {
		let text = '';
		const possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		for (let i = 0; i < idLength; i++) text += possible.charAt(Math.floor(Math.random() * possible.length));
		return text;
	}

	/**Function that clears and stops execution of debouncers */
	clearTimers() {
		this._AS_debounces.forEach(to => {
			clearTimer(to);
		});
		this._AS_debounces = [];
	}

	/**Alias to getBoundingClientRect that returns a mutable object*/
	getBoundingClientRect(element) {
		var rect = element.getBoundingClientRect();
		return {
			top: rect.top,
			right: rect.right,
			bottom: rect.bottom,
			left: rect.left,
			width: rect.width,
			height: rect.height,
			x: rect.x,
			y: rect.y
		};
	}
}