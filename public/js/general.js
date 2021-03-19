/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./resources/js/general.js":
/*!*********************************!*\
  !*** ./resources/js/general.js ***!
  \*********************************/
/***/ (() => {

window.ajax = function (url, data) {
  var callBack = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;
  fetch(url, {
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json, text-plain, */*",
      "X-Requested-With": "XMLHttpRequest",
      "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value
    },
    method: "post",
    credentials: "same-origin",
    body: JSON.stringify(data)
  }).then(function (response) {
    return response.json();
  }).then(function (response) {
    if (callBack) callBack(response); // console.log (callBack);
  })["catch"](function (error) {
    console.log(error);
  });
};

window.modal = function (action) {
  var title = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
  var text = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;
  var button1 = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : null;
  var button2 = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : null;
  var modal = document.getElementById("modal");
  var modalTitle = modal.querySelector(".modal-title");
  var modalText = modal.querySelector(".modal-text");
  var modalButtons = modal.querySelector(".modal-buttons");
  var modalButton1 = modal.querySelector(".modal-button1");
  var modalButton2 = modal.querySelector(".modal-button2");

  if (action == "open") {
    if (modal.classList.contains("hide")) modal.classList.remove("hide");

    if (title) {
      if (modalTitle.classList.contains("hide")) modalTitle.classList.remove("hide");
      modalTitle.innerHTML = title;
    }

    if (text) {
      if (modalText.classList.contains("hide")) modalText.classList.remove("hide");
      modalText.innerHTML = text;
    }

    if (button1) {
      if (modalButtons.classList.contains("hide")) modalButtons.classList.remove("hide");
      if (modalButton1.classList.contains("hide")) modalButton1.classList.remove("hide");
      modalButton1.innerHTML = button1.name;
      modalButton1.onclick = button1["function"];
    }

    if (button2) {
      if (modalButtons.classList.contains("hide")) modalButtons.classList.remove("hide");
      if (modalButton2.classList.contains("hide")) modalButton2.classList.remove("hide");
      modalButton2.innerHTML = button2.name;
      modalButton2.onclick = button2["function"];
    }

    document.addEventListener("keydown", escapeModal = function escapeModal(e) {
      var keyCode = e.keyCode;

      if (keyCode === 27) {
        //keycode is an Integer, not a String
        window.modal("close");
      }
    });
  } else {
    document.removeEventListener("keydown", escapeModal);
    modal.classList.add("hide");
    modalButtons.classList.add("hide");
    modalTitle.innerHTML = "";
    modalText.innerHTML = "";
    modalButton1.innerHTML = "";
    modalButton1.onclick = null;
    modalButton2.innerHTML = "";
    modalButton2.onclick = null;
    modalTitle.classList.add("hide");
    modalText.classList.add("hide");
    modalButton1.classList.add("hide");
    modalButton2.classList.add("hide");
  }
};

window.modalFromTask = function (props) {
  // generalinfo: "Холсты 30х40" -
  // info: null -
  // master: 4 -
  // name: "Натяжка холста на подрамник" -
  // start: "2021-03-11 16:40:00"
  // time: 6
  // console.log (props)
  var html = "";
  html += '<form id="detail-task-form" action="/saveedittask" method="POST">';
  html += "<input type=\"hidden\" name=\"id\" value=\"".concat(props.id, "\">");
  html += "<input type=\"hidden\" name=\"_token\" value=\"".concat(document.querySelector('input[name="_token"]').value, "\">");
  html += '<div class="form-block">';
  html += '<div class="form-elem">';
  html += '<label for="name">Название задачи</label>';
  html += "<input class=\"input-required\" type=\"text\" name=\"name\" value=\"".concat(props.name, "\">");
  html += "</div>";
  html += '<div class="form-elem">';
  html += '<label for="generalinfo">Основные параметры</label>';
  html += "<input class=\"input-required\" type=\"text\" name=\"generalinfo\" value=\"".concat(props.generalinfo, "\">");
  html += "</div>";
  html += '<div class="form-elem">';
  html += '<label for="info">Дополнительные параметры</label>';
  html += "<input type=\"text\" name=\"info\" value=\"".concat(props.info, "\">");
  html += "</div>";
  html += "</div>";
  html += '<div class="form-block">';
  html += '<div class="form-elem form-elem__min">';
  html += '<label for="master">ID Мастера</label>';
  html += "<input class=\"input-required\" list=\"masters\" name=\"master\" value=\"".concat(props.master, "\">");
  html += "</div>";
  html += '<div class="form-elem form-elem__min">';
  html += '<label for="start">Дата начала задачи</label>';
  html += "<input class=\"input-required\" type=\"text\" name=\"start\" value=\"".concat(props.start, "\">");
  html += "</div>";
  html += '<div class="form-elem form-elem__min">';
  html += '<label for="time">Время, мин.</label>';
  html += "<input class=\"input-required\" type=\"text\" name=\"time\" value=\"".concat(props.time, "\">");
  html += "</div>";
  html += '<div class="form-elem form-elem__min">';
  html += '<label for="bufer">Буфер, мин.</label>';
  html += "<input class=\"input-required\" type=\"text\" name=\"bufer\" value=\"".concat(props.buffer, "\">");
  html += "</div>";
  html += '<div class="form-elem form-elem__min">';
  html += '<label for="status">Статус задачи:</label><select id="status" name="status">';
  var statusArr = [{
    status: 'wait',
    name: 'Ожидает исполнения'
  }, {
    status: 'pause',
    name: 'Остановлена'
  }, {
    status: 'finished',
    name: 'Завершена'
  }];
  statusArr.forEach(function (el) {
    html += "<option value=\"".concat(el.status, "\" ").concat(el.status === props.status ? 'selected' : '', ">").concat(el.name, "</option>");
  });
  html += '</select>';
  html += "</div>";
  html += "</div>";
  html += '<button class="form-modal-button" type="button" onclick="detailTaskFormSubmit()">Сохранить</button>';
  if (typeof props.id !== 'undefined') html += '<input class="delete-confirm" type="checkbox" name="deleteconfirm"><labelfor="deleteconfirm">Удалить задачу?</label>';
  html += "</form>";
  modal("open", null, html);
};

window.detailTaskFormSubmit = function () {
  var requaredInputs = document.querySelectorAll(".input-required");
  var alert = 0;
  requaredInputs.forEach(function (el) {
    if (el.value === "") {
      el.classList.add("form-alert");
      alert++;
    } else {
      if (el.classList.contains("form-alert")) el.classList.remove("form-alert");
    }
  });
  if (alert === 0) document.getElementById('detail-task-form').submit();
};

/***/ }),

/***/ "./resources/sass/calendar.scss":
/*!**************************************!*\
  !*** ./resources/sass/calendar.scss ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./resources/sass/board.scss":
/*!***********************************!*\
  !*** ./resources/sass/board.scss ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./resources/sass/newtemplate.scss":
/*!*****************************************!*\
  !*** ./resources/sass/newtemplate.scss ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./resources/sass/welcome.scss":
/*!*************************************!*\
  !*** ./resources/sass/welcome.scss ***!
  \*************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./resources/sass/general.scss":
/*!*************************************!*\
  !*** ./resources/sass/general.scss ***!
  \*************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./resources/sass/tasklist.scss":
/*!**************************************!*\
  !*** ./resources/sass/tasklist.scss ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./resources/sass/startnewdeal.scss":
/*!******************************************!*\
  !*** ./resources/sass/startnewdeal.scss ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		if(__webpack_module_cache__[moduleId]) {
/******/ 			return __webpack_module_cache__[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/******/ 	// the startup function
/******/ 	// It's empty as some runtime module handles the default behavior
/******/ 	__webpack_require__.x = x => {}
/************************************************************************/
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => Object.prototype.hasOwnProperty.call(obj, prop)
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// Promise = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"/js/general": 0
/******/ 		};
/******/ 		
/******/ 		var deferredModules = [
/******/ 			["./resources/js/general.js"],
/******/ 			["./resources/sass/newtemplate.scss"],
/******/ 			["./resources/sass/welcome.scss"],
/******/ 			["./resources/sass/general.scss"],
/******/ 			["./resources/sass/tasklist.scss"],
/******/ 			["./resources/sass/startnewdeal.scss"],
/******/ 			["./resources/sass/calendar.scss"],
/******/ 			["./resources/sass/board.scss"]
/******/ 		];
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		var checkDeferredModules = x => {};
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime, executeModules] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0, resolves = [];
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					resolves.push(installedChunks[chunkId][0]);
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			for(moduleId in moreModules) {
/******/ 				if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 					__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 				}
/******/ 			}
/******/ 			if(runtime) runtime(__webpack_require__);
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			while(resolves.length) {
/******/ 				resolves.shift()();
/******/ 			}
/******/ 		
/******/ 			// add entry modules from loaded chunk to deferred list
/******/ 			if(executeModules) deferredModules.push.apply(deferredModules, executeModules);
/******/ 		
/******/ 			// run deferred modules when all chunks ready
/******/ 			return checkDeferredModules();
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = self["webpackChunk"] = self["webpackChunk"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 		
/******/ 		function checkDeferredModulesImpl() {
/******/ 			var result;
/******/ 			for(var i = 0; i < deferredModules.length; i++) {
/******/ 				var deferredModule = deferredModules[i];
/******/ 				var fulfilled = true;
/******/ 				for(var j = 1; j < deferredModule.length; j++) {
/******/ 					var depId = deferredModule[j];
/******/ 					if(installedChunks[depId] !== 0) fulfilled = false;
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferredModules.splice(i--, 1);
/******/ 					result = __webpack_require__(__webpack_require__.s = deferredModule[0]);
/******/ 				}
/******/ 			}
/******/ 			if(deferredModules.length === 0) {
/******/ 				__webpack_require__.x();
/******/ 				__webpack_require__.x = x => {};
/******/ 			}
/******/ 			return result;
/******/ 		}
/******/ 		var startup = __webpack_require__.x;
/******/ 		__webpack_require__.x = () => {
/******/ 			// reset startup function so it can be called again when more startup code is added
/******/ 			__webpack_require__.x = startup || (x => {});
/******/ 			return (checkDeferredModules = checkDeferredModulesImpl)();
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	// run startup
/******/ 	__webpack_require__.x();
/******/ })()
;