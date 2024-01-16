/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/@babel/runtime/regenerator/index.js":
/*!**********************************************************!*\
  !*** ./node_modules/@babel/runtime/regenerator/index.js ***!
  \**********************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

module.exports = __webpack_require__(/*! regenerator-runtime */ "./node_modules/regenerator-runtime/runtime.js");

/***/ }),

/***/ "./node_modules/country-code-emoji/lib/index.umd.js":
/*!**********************************************************!*\
  !*** ./node_modules/country-code-emoji/lib/index.umd.js ***!
  \**********************************************************/
/***/ (function(module) {

!function (t, r) {
   true ? module.exports = r() : 0;
}(this, function () {
  "use strict";

  function _typeof(t) {
    return (_typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (t) {
      return typeof t;
    } : function (t) {
      return t && "function" == typeof Symbol && t.constructor === Symbol && t !== Symbol.prototype ? "symbol" : typeof t;
    })(t);
  }
  function _toConsumableArray(t) {
    return function _arrayWithoutHoles(t) {
      if (Array.isArray(t)) {
        for (var r = 0, o = new Array(t.length); r < t.length; r++) {
          o[r] = t[r];
        }
        return o;
      }
    }(t) || function _iterableToArray(t) {
      if (Symbol.iterator in Object(t) || "[object Arguments]" === Object.prototype.toString.call(t)) return Array.from(t);
    }(t) || function _nonIterableSpread() {
      throw new TypeError("Invalid attempt to spread non-iterable instance");
    }();
  }
  var t = /^[a-z]{2}$/i,
    r = 127397;
  return function countryCodeEmoji(o) {
    if (!t.test(o)) {
      var e = _typeof(o);
      throw new TypeError("cc argument must be an ISO 3166-1 alpha-2 string, but got '".concat("string" === e ? o : e, "' instead."));
    }
    var n = _toConsumableArray(o.toUpperCase()).map(function (t) {
      return t.charCodeAt() + r;
    });
    return String.fromCodePoint.apply(String, _toConsumableArray(n));
  };
});

/***/ }),

/***/ "./node_modules/external-load/index.js":
/*!*********************************************!*\
  !*** ./node_modules/external-load/index.js ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**
 * Simple resource loader based on David Walsh's tutorial
 * https://davidwalsh.name/javascript-loader
 * https://davidwalsh.name/javascript-functions
 */
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((function () {
  // Function which returns a function
  function _load(tag) {
    return function (url) {
      // This promise will be used by Promise.all to determine success or failure
      return new Promise(function (resolve, reject) {
        var element = document.createElement(tag);
        var parent = "body";
        var attr = "src";

        // Important success and error for the promise
        element.onload = function () {
          resolve(url);
        };
        element.onerror = function () {
          reject(url);
        };

        // Need to set different attributes depending on tag type
        switch (tag) {
          case "script":
            element.async = true;
            break;
          case "link":
            element.type = "text/css";
            element.rel = "stylesheet";
            attr = "href";
            parent = "head";
        }

        // Inject into document to kick off loading
        element[attr] = url;
        document[parent].appendChild(element);
      });
    };
  }
  return {
    css: _load("link"),
    js: _load("script"),
    img: _load("img")
  };
})());

/***/ }),

/***/ "./node_modules/regenerator-runtime/runtime.js":
/*!*****************************************************!*\
  !*** ./node_modules/regenerator-runtime/runtime.js ***!
  \*****************************************************/
/***/ ((module) => {

/**
 * Copyright (c) 2014-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

var runtime = function (exports) {
  "use strict";

  var Op = Object.prototype;
  var hasOwn = Op.hasOwnProperty;
  var undefined; // More compressible than void 0.
  var $Symbol = typeof Symbol === "function" ? Symbol : {};
  var iteratorSymbol = $Symbol.iterator || "@@iterator";
  var asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator";
  var toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag";
  function define(obj, key, value) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
    return obj[key];
  }
  try {
    // IE 8 has a broken Object.defineProperty that only works on DOM objects.
    define({}, "");
  } catch (err) {
    define = function define(obj, key, value) {
      return obj[key] = value;
    };
  }
  function wrap(innerFn, outerFn, self, tryLocsList) {
    // If outerFn provided and outerFn.prototype is a Generator, then outerFn.prototype instanceof Generator.
    var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator;
    var generator = Object.create(protoGenerator.prototype);
    var context = new Context(tryLocsList || []);

    // The ._invoke method unifies the implementations of the .next,
    // .throw, and .return methods.
    generator._invoke = makeInvokeMethod(innerFn, self, context);
    return generator;
  }
  exports.wrap = wrap;

  // Try/catch helper to minimize deoptimizations. Returns a completion
  // record like context.tryEntries[i].completion. This interface could
  // have been (and was previously) designed to take a closure to be
  // invoked without arguments, but in all the cases we care about we
  // already have an existing method we want to call, so there's no need
  // to create a new function object. We can even get away with assuming
  // the method takes exactly one argument, since that happens to be true
  // in every case, so we don't have to touch the arguments object. The
  // only additional allocation required is the completion record, which
  // has a stable shape and so hopefully should be cheap to allocate.
  function tryCatch(fn, obj, arg) {
    try {
      return {
        type: "normal",
        arg: fn.call(obj, arg)
      };
    } catch (err) {
      return {
        type: "throw",
        arg: err
      };
    }
  }
  var GenStateSuspendedStart = "suspendedStart";
  var GenStateSuspendedYield = "suspendedYield";
  var GenStateExecuting = "executing";
  var GenStateCompleted = "completed";

  // Returning this object from the innerFn has the same effect as
  // breaking out of the dispatch switch statement.
  var ContinueSentinel = {};

  // Dummy constructor functions that we use as the .constructor and
  // .constructor.prototype properties for functions that return Generator
  // objects. For full spec compliance, you may wish to configure your
  // minifier not to mangle the names of these two functions.
  function Generator() {}
  function GeneratorFunction() {}
  function GeneratorFunctionPrototype() {}

  // This is a polyfill for %IteratorPrototype% for environments that
  // don't natively support it.
  var IteratorPrototype = {};
  define(IteratorPrototype, iteratorSymbol, function () {
    return this;
  });
  var getProto = Object.getPrototypeOf;
  var NativeIteratorPrototype = getProto && getProto(getProto(values([])));
  if (NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol)) {
    // This environment has a native %IteratorPrototype%; use it instead
    // of the polyfill.
    IteratorPrototype = NativeIteratorPrototype;
  }
  var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype);
  GeneratorFunction.prototype = GeneratorFunctionPrototype;
  define(Gp, "constructor", GeneratorFunctionPrototype);
  define(GeneratorFunctionPrototype, "constructor", GeneratorFunction);
  GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction");

  // Helper for defining the .next, .throw, and .return methods of the
  // Iterator interface in terms of a single ._invoke method.
  function defineIteratorMethods(prototype) {
    ["next", "throw", "return"].forEach(function (method) {
      define(prototype, method, function (arg) {
        return this._invoke(method, arg);
      });
    });
  }
  exports.isGeneratorFunction = function (genFun) {
    var ctor = typeof genFun === "function" && genFun.constructor;
    return ctor ? ctor === GeneratorFunction ||
    // For the native GeneratorFunction constructor, the best we can
    // do is to check its .name property.
    (ctor.displayName || ctor.name) === "GeneratorFunction" : false;
  };
  exports.mark = function (genFun) {
    if (Object.setPrototypeOf) {
      Object.setPrototypeOf(genFun, GeneratorFunctionPrototype);
    } else {
      genFun.__proto__ = GeneratorFunctionPrototype;
      define(genFun, toStringTagSymbol, "GeneratorFunction");
    }
    genFun.prototype = Object.create(Gp);
    return genFun;
  };

  // Within the body of any async function, `await x` is transformed to
  // `yield regeneratorRuntime.awrap(x)`, so that the runtime can test
  // `hasOwn.call(value, "__await")` to determine if the yielded value is
  // meant to be awaited.
  exports.awrap = function (arg) {
    return {
      __await: arg
    };
  };
  function AsyncIterator(generator, PromiseImpl) {
    function invoke(method, arg, resolve, reject) {
      var record = tryCatch(generator[method], generator, arg);
      if (record.type === "throw") {
        reject(record.arg);
      } else {
        var result = record.arg;
        var value = result.value;
        if (value && typeof value === "object" && hasOwn.call(value, "__await")) {
          return PromiseImpl.resolve(value.__await).then(function (value) {
            invoke("next", value, resolve, reject);
          }, function (err) {
            invoke("throw", err, resolve, reject);
          });
        }
        return PromiseImpl.resolve(value).then(function (unwrapped) {
          // When a yielded Promise is resolved, its final value becomes
          // the .value of the Promise<{value,done}> result for the
          // current iteration.
          result.value = unwrapped;
          resolve(result);
        }, function (error) {
          // If a rejected Promise was yielded, throw the rejection back
          // into the async generator function so it can be handled there.
          return invoke("throw", error, resolve, reject);
        });
      }
    }
    var previousPromise;
    function enqueue(method, arg) {
      function callInvokeWithMethodAndArg() {
        return new PromiseImpl(function (resolve, reject) {
          invoke(method, arg, resolve, reject);
        });
      }
      return previousPromise =
      // If enqueue has been called before, then we want to wait until
      // all previous Promises have been resolved before calling invoke,
      // so that results are always delivered in the correct order. If
      // enqueue has not been called before, then it is important to
      // call invoke immediately, without waiting on a callback to fire,
      // so that the async generator function has the opportunity to do
      // any necessary setup in a predictable way. This predictability
      // is why the Promise constructor synchronously invokes its
      // executor callback, and why async functions synchronously
      // execute code before the first await. Since we implement simple
      // async functions in terms of async generators, it is especially
      // important to get this right, even though it requires care.
      previousPromise ? previousPromise.then(callInvokeWithMethodAndArg,
      // Avoid propagating failures to Promises returned by later
      // invocations of the iterator.
      callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg();
    }

    // Define the unified helper method that is used to implement .next,
    // .throw, and .return (see defineIteratorMethods).
    this._invoke = enqueue;
  }
  defineIteratorMethods(AsyncIterator.prototype);
  define(AsyncIterator.prototype, asyncIteratorSymbol, function () {
    return this;
  });
  exports.AsyncIterator = AsyncIterator;

  // Note that simple async functions are implemented on top of
  // AsyncIterator objects; they just return a Promise for the value of
  // the final result produced by the iterator.
  exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) {
    if (PromiseImpl === void 0) PromiseImpl = Promise;
    var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl);
    return exports.isGeneratorFunction(outerFn) ? iter // If outerFn is a generator, return the full iterator.
    : iter.next().then(function (result) {
      return result.done ? result.value : iter.next();
    });
  };
  function makeInvokeMethod(innerFn, self, context) {
    var state = GenStateSuspendedStart;
    return function invoke(method, arg) {
      if (state === GenStateExecuting) {
        throw new Error("Generator is already running");
      }
      if (state === GenStateCompleted) {
        if (method === "throw") {
          throw arg;
        }

        // Be forgiving, per 25.3.3.3.3 of the spec:
        // https://people.mozilla.org/~jorendorff/es6-draft.html#sec-generatorresume
        return doneResult();
      }
      context.method = method;
      context.arg = arg;
      while (true) {
        var delegate = context.delegate;
        if (delegate) {
          var delegateResult = maybeInvokeDelegate(delegate, context);
          if (delegateResult) {
            if (delegateResult === ContinueSentinel) continue;
            return delegateResult;
          }
        }
        if (context.method === "next") {
          // Setting context._sent for legacy support of Babel's
          // function.sent implementation.
          context.sent = context._sent = context.arg;
        } else if (context.method === "throw") {
          if (state === GenStateSuspendedStart) {
            state = GenStateCompleted;
            throw context.arg;
          }
          context.dispatchException(context.arg);
        } else if (context.method === "return") {
          context.abrupt("return", context.arg);
        }
        state = GenStateExecuting;
        var record = tryCatch(innerFn, self, context);
        if (record.type === "normal") {
          // If an exception is thrown from innerFn, we leave state ===
          // GenStateExecuting and loop back for another invocation.
          state = context.done ? GenStateCompleted : GenStateSuspendedYield;
          if (record.arg === ContinueSentinel) {
            continue;
          }
          return {
            value: record.arg,
            done: context.done
          };
        } else if (record.type === "throw") {
          state = GenStateCompleted;
          // Dispatch the exception by looping back around to the
          // context.dispatchException(context.arg) call above.
          context.method = "throw";
          context.arg = record.arg;
        }
      }
    };
  }

  // Call delegate.iterator[context.method](context.arg) and handle the
  // result, either by returning a { value, done } result from the
  // delegate iterator, or by modifying context.method and context.arg,
  // setting context.delegate to null, and returning the ContinueSentinel.
  function maybeInvokeDelegate(delegate, context) {
    var method = delegate.iterator[context.method];
    if (method === undefined) {
      // A .throw or .return when the delegate iterator has no .throw
      // method always terminates the yield* loop.
      context.delegate = null;
      if (context.method === "throw") {
        // Note: ["return"] must be used for ES3 parsing compatibility.
        if (delegate.iterator["return"]) {
          // If the delegate iterator has a return method, give it a
          // chance to clean up.
          context.method = "return";
          context.arg = undefined;
          maybeInvokeDelegate(delegate, context);
          if (context.method === "throw") {
            // If maybeInvokeDelegate(context) changed context.method from
            // "return" to "throw", let that override the TypeError below.
            return ContinueSentinel;
          }
        }
        context.method = "throw";
        context.arg = new TypeError("The iterator does not provide a 'throw' method");
      }
      return ContinueSentinel;
    }
    var record = tryCatch(method, delegate.iterator, context.arg);
    if (record.type === "throw") {
      context.method = "throw";
      context.arg = record.arg;
      context.delegate = null;
      return ContinueSentinel;
    }
    var info = record.arg;
    if (!info) {
      context.method = "throw";
      context.arg = new TypeError("iterator result is not an object");
      context.delegate = null;
      return ContinueSentinel;
    }
    if (info.done) {
      // Assign the result of the finished delegate to the temporary
      // variable specified by delegate.resultName (see delegateYield).
      context[delegate.resultName] = info.value;

      // Resume execution at the desired location (see delegateYield).
      context.next = delegate.nextLoc;

      // If context.method was "throw" but the delegate handled the
      // exception, let the outer generator proceed normally. If
      // context.method was "next", forget context.arg since it has been
      // "consumed" by the delegate iterator. If context.method was
      // "return", allow the original .return call to continue in the
      // outer generator.
      if (context.method !== "return") {
        context.method = "next";
        context.arg = undefined;
      }
    } else {
      // Re-yield the result returned by the delegate method.
      return info;
    }

    // The delegate iterator is finished, so forget it and continue with
    // the outer generator.
    context.delegate = null;
    return ContinueSentinel;
  }

  // Define Generator.prototype.{next,throw,return} in terms of the
  // unified ._invoke helper method.
  defineIteratorMethods(Gp);
  define(Gp, toStringTagSymbol, "Generator");

  // A Generator should always return itself as the iterator object when the
  // @@iterator function is called on it. Some browsers' implementations of the
  // iterator prototype chain incorrectly implement this, causing the Generator
  // object to not be returned from this call. This ensures that doesn't happen.
  // See https://github.com/facebook/regenerator/issues/274 for more details.
  define(Gp, iteratorSymbol, function () {
    return this;
  });
  define(Gp, "toString", function () {
    return "[object Generator]";
  });
  function pushTryEntry(locs) {
    var entry = {
      tryLoc: locs[0]
    };
    if (1 in locs) {
      entry.catchLoc = locs[1];
    }
    if (2 in locs) {
      entry.finallyLoc = locs[2];
      entry.afterLoc = locs[3];
    }
    this.tryEntries.push(entry);
  }
  function resetTryEntry(entry) {
    var record = entry.completion || {};
    record.type = "normal";
    delete record.arg;
    entry.completion = record;
  }
  function Context(tryLocsList) {
    // The root entry object (effectively a try statement without a catch
    // or a finally block) gives us a place to store values thrown from
    // locations where there is no enclosing try statement.
    this.tryEntries = [{
      tryLoc: "root"
    }];
    tryLocsList.forEach(pushTryEntry, this);
    this.reset(true);
  }
  exports.keys = function (object) {
    var keys = [];
    for (var key in object) {
      keys.push(key);
    }
    keys.reverse();

    // Rather than returning an object with a next method, we keep
    // things simple and return the next function itself.
    return function next() {
      while (keys.length) {
        var key = keys.pop();
        if (key in object) {
          next.value = key;
          next.done = false;
          return next;
        }
      }

      // To avoid creating an additional object, we just hang the .value
      // and .done properties off the next function object itself. This
      // also ensures that the minifier will not anonymize the function.
      next.done = true;
      return next;
    };
  };
  function values(iterable) {
    if (iterable) {
      var iteratorMethod = iterable[iteratorSymbol];
      if (iteratorMethod) {
        return iteratorMethod.call(iterable);
      }
      if (typeof iterable.next === "function") {
        return iterable;
      }
      if (!isNaN(iterable.length)) {
        var i = -1,
          next = function next() {
            while (++i < iterable.length) {
              if (hasOwn.call(iterable, i)) {
                next.value = iterable[i];
                next.done = false;
                return next;
              }
            }
            next.value = undefined;
            next.done = true;
            return next;
          };
        return next.next = next;
      }
    }

    // Return an iterator with no values.
    return {
      next: doneResult
    };
  }
  exports.values = values;
  function doneResult() {
    return {
      value: undefined,
      done: true
    };
  }
  Context.prototype = {
    constructor: Context,
    reset: function reset(skipTempReset) {
      this.prev = 0;
      this.next = 0;
      // Resetting context._sent for legacy support of Babel's
      // function.sent implementation.
      this.sent = this._sent = undefined;
      this.done = false;
      this.delegate = null;
      this.method = "next";
      this.arg = undefined;
      this.tryEntries.forEach(resetTryEntry);
      if (!skipTempReset) {
        for (var name in this) {
          // Not sure about the optimal order of these conditions:
          if (name.charAt(0) === "t" && hasOwn.call(this, name) && !isNaN(+name.slice(1))) {
            this[name] = undefined;
          }
        }
      }
    },
    stop: function stop() {
      this.done = true;
      var rootEntry = this.tryEntries[0];
      var rootRecord = rootEntry.completion;
      if (rootRecord.type === "throw") {
        throw rootRecord.arg;
      }
      return this.rval;
    },
    dispatchException: function dispatchException(exception) {
      if (this.done) {
        throw exception;
      }
      var context = this;
      function handle(loc, caught) {
        record.type = "throw";
        record.arg = exception;
        context.next = loc;
        if (caught) {
          // If the dispatched exception was caught by a catch block,
          // then let that catch block handle the exception normally.
          context.method = "next";
          context.arg = undefined;
        }
        return !!caught;
      }
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        var record = entry.completion;
        if (entry.tryLoc === "root") {
          // Exception thrown outside of any try block that could handle
          // it, so set the completion value of the entire function to
          // throw the exception.
          return handle("end");
        }
        if (entry.tryLoc <= this.prev) {
          var hasCatch = hasOwn.call(entry, "catchLoc");
          var hasFinally = hasOwn.call(entry, "finallyLoc");
          if (hasCatch && hasFinally) {
            if (this.prev < entry.catchLoc) {
              return handle(entry.catchLoc, true);
            } else if (this.prev < entry.finallyLoc) {
              return handle(entry.finallyLoc);
            }
          } else if (hasCatch) {
            if (this.prev < entry.catchLoc) {
              return handle(entry.catchLoc, true);
            }
          } else if (hasFinally) {
            if (this.prev < entry.finallyLoc) {
              return handle(entry.finallyLoc);
            }
          } else {
            throw new Error("try statement without catch or finally");
          }
        }
      }
    },
    abrupt: function abrupt(type, arg) {
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) {
          var finallyEntry = entry;
          break;
        }
      }
      if (finallyEntry && (type === "break" || type === "continue") && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc) {
        // Ignore the finally entry if control is not jumping to a
        // location outside the try/catch block.
        finallyEntry = null;
      }
      var record = finallyEntry ? finallyEntry.completion : {};
      record.type = type;
      record.arg = arg;
      if (finallyEntry) {
        this.method = "next";
        this.next = finallyEntry.finallyLoc;
        return ContinueSentinel;
      }
      return this.complete(record);
    },
    complete: function complete(record, afterLoc) {
      if (record.type === "throw") {
        throw record.arg;
      }
      if (record.type === "break" || record.type === "continue") {
        this.next = record.arg;
      } else if (record.type === "return") {
        this.rval = this.arg = record.arg;
        this.method = "return";
        this.next = "end";
      } else if (record.type === "normal" && afterLoc) {
        this.next = afterLoc;
      }
      return ContinueSentinel;
    },
    finish: function finish(finallyLoc) {
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        if (entry.finallyLoc === finallyLoc) {
          this.complete(entry.completion, entry.afterLoc);
          resetTryEntry(entry);
          return ContinueSentinel;
        }
      }
    },
    "catch": function _catch(tryLoc) {
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        if (entry.tryLoc === tryLoc) {
          var record = entry.completion;
          if (record.type === "throw") {
            var thrown = record.arg;
            resetTryEntry(entry);
          }
          return thrown;
        }
      }

      // The context.catch method must only be called with a location
      // argument that corresponds to a known catch block.
      throw new Error("illegal catch attempt");
    },
    delegateYield: function delegateYield(iterable, resultName, nextLoc) {
      this.delegate = {
        iterator: values(iterable),
        resultName: resultName,
        nextLoc: nextLoc
      };
      if (this.method === "next") {
        // Deliberately forget the last sent value so that we don't
        // accidentally pass it on to the delegate.
        this.arg = undefined;
      }
      return ContinueSentinel;
    }
  };

  // Regardless of whether this script is executing as a CommonJS module
  // or not, return the runtime object so that we can declare the variable
  // regeneratorRuntime in the outer scope, which allows this module to be
  // injected easily by `bin/regenerator --include-runtime script.js`.
  return exports;
}(
// If this script is executing as a CommonJS module, use module.exports
// as the regeneratorRuntime namespace. Otherwise create a new empty
// object. Either way, the resulting object will be used to initialize
// the regeneratorRuntime variable at the top of this file.
 true ? module.exports : 0);
try {
  regeneratorRuntime = runtime;
} catch (accidentalStrictMode) {
  // This module should not be running in strict mode, so the above
  // assignment should always work unless something is misconfigured. Just
  // in case runtime.js accidentally runs in strict mode, in modern engines
  // we can explicitly access globalThis. In older engines we can escape
  // strict mode using a global Function call. This could conceivably fail
  // if a Content Security Policy forbids using Function, but in that case
  // the proper solution is to fix the accidental strict mode problem. If
  // you've misconfigured your bundler to force strict mode and applied a
  // CSP to forbid Function, and you're not willing to fix either of those
  // problems, please detail your unique predicament in a GitHub issue.
  if (typeof globalThis === "object") {
    globalThis.regeneratorRuntime = runtime;
  } else {
    Function("r", "regeneratorRuntime = r")(runtime);
  }
}

/***/ }),

/***/ "./node_modules/twemoji-basename/index.js":
/*!************************************************!*\
  !*** ./node_modules/twemoji-basename/index.js ***!
  \************************************************/
/***/ ((module) => {

/*! Copyright Twitter Inc. and other contributors. Licensed under MIT */
/* https://github.com/twitter/twemoji/blob/gh-pages/LICENSE */

module.exports = function (str) {
  var r = [];
  var c = 0;
  var p = 0;
  var i = 0;
  var l = str.length;
  while (i < l) {
    c = str.charCodeAt(i++);
    if (p) {
      r.push((0x10000 + (p - 0xD800 << 10) + (c - 0xDC00)).toString(16));
      p = 0;
    } else if (0xD800 <= c && c <= 0xDBFF) {
      p = c;
    } else {
      r.push(c.toString(16));
    }
  }
  return r.join('-');
};

/***/ }),

/***/ "./src/forum/components/MapModal.tsx":
/*!*******************************************!*\
  !*** ./src/forum/components/MapModal.tsx ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ MapModal)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/components/Modal */ "flarum/common/components/Modal");
/* harmony import */ var flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _ZipCodeMap__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./ZipCodeMap */ "./src/forum/components/ZipCodeMap.js");
/* harmony import */ var _helpers_ClipboardHelper__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../helpers/ClipboardHelper */ "./src/forum/helpers/ClipboardHelper.ts");
/* harmony import */ var flarum_common_components_LabelValue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! flarum/common/components/LabelValue */ "flarum/common/components/LabelValue");
/* harmony import */ var flarum_common_components_LabelValue__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_LabelValue__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! flarum/common/components/LoadingIndicator */ "flarum/common/components/LoadingIndicator");
/* harmony import */ var flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_6__);







var MapModal = /*#__PURE__*/function (_Modal) {
  (0,_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(MapModal, _Modal);
  function MapModal() {
    var _this;
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }
    _this = _Modal.call.apply(_Modal, [this].concat(args)) || this;
    _this.ipInfo = void 0;
    return _this;
  }
  var _proto = MapModal.prototype;
  _proto.oninit = function oninit(vnode) {
    _Modal.prototype.oninit.call(this, vnode);
    this.ipInfo = this.attrs.ipInfo;
    if (this.ipInfo === undefined) {
      this.loadIpInfo();
    }
  };
  _proto.className = function className() {
    return 'MapModal Modal--medium';
  };
  _proto.loadIpInfo = function loadIpInfo() {
    var _this2 = this;
    flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().store.find('ip_info', encodeURIComponent(this.attrs.ipAddr)).then(function (ipInfo) {
      _this2.ipInfo = ipInfo;
      m.redraw();
    })["catch"](function (error) {
      console.error('Failed to load IP information from the store', error);
    });
  };
  _proto.title = function title() {
    return flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('fof-geoip.forum.map_modal.title');
  };
  _proto.content = function content() {
    var ipInfo = this.ipInfo;
    if (!ipInfo) {
      return m("div", {
        className: "Modal-body"
      }, m((flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_6___default()), null));
    }
    return m("div", {
      className: "Modal-body"
    }, m("div", {
      className: "IPDetails"
    }, m((flarum_common_components_LabelValue__WEBPACK_IMPORTED_MODULE_5___default()), {
      label: flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('fof-geoip.forum.map_modal.ip_address'),
      value: m("span", {
        className: "clickable-ip",
        onclick: (0,_helpers_ClipboardHelper__WEBPACK_IMPORTED_MODULE_4__.handleCopyIP)(this.attrs.ipAddr)
      }, this.attrs.ipAddr)
    }), ipInfo.countryCode() && m((flarum_common_components_LabelValue__WEBPACK_IMPORTED_MODULE_5___default()), {
      label: flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('fof-geoip.forum.map_modal.country_code'),
      value: ipInfo.countryCode()
    }), ipInfo.zipCode() && m((flarum_common_components_LabelValue__WEBPACK_IMPORTED_MODULE_5___default()), {
      label: flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('fof-geoip.forum.map_modal.zip_code'),
      value: ipInfo.zipCode()
    }), ipInfo.isp() && m((flarum_common_components_LabelValue__WEBPACK_IMPORTED_MODULE_5___default()), {
      label: flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('fof-geoip.forum.map_modal.isp'),
      value: ipInfo.isp()
    }), ipInfo.organization() && m((flarum_common_components_LabelValue__WEBPACK_IMPORTED_MODULE_5___default()), {
      label: flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('fof-geoip.forum.map_modal.organization'),
      value: ipInfo.organization()
    }), ipInfo.as() && m((flarum_common_components_LabelValue__WEBPACK_IMPORTED_MODULE_5___default()), {
      label: flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('fof-geoip.forum.map_modal.as'),
      value: ipInfo.as()
    }), m((flarum_common_components_LabelValue__WEBPACK_IMPORTED_MODULE_5___default()), {
      label: flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('fof-geoip.forum.map_modal.mobile'),
      value: ipInfo.mobile() ? 'yes' : 'no'
    }), ipInfo.threatLevel() && m((flarum_common_components_LabelValue__WEBPACK_IMPORTED_MODULE_5___default()), {
      label: flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('fof-geoip.forum.map_modal.threat_level'),
      value: ipInfo.threatLevel()
    }), ipInfo.threatTypes().length > 0 && m((flarum_common_components_LabelValue__WEBPACK_IMPORTED_MODULE_5___default()), {
      label: flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('fof-geoip.forum.map_modal.threat_types'),
      value: ipInfo.threatTypes().join(', ')
    }), ipInfo.error() && m((flarum_common_components_LabelValue__WEBPACK_IMPORTED_MODULE_5___default()), {
      label: flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('fof-geoip.forum.map_modal.error'),
      value: ipInfo.error()
    })), m("hr", null), m("div", {
      id: "mapContainer"
    }, m(_ZipCodeMap__WEBPACK_IMPORTED_MODULE_3__["default"], {
      ipInfo: ipInfo
    })));
  };
  return MapModal;
}((flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2___default()));


/***/ }),

/***/ "./src/forum/components/ZipCodeMap.js":
/*!********************************************!*\
  !*** ./src/forum/components/ZipCodeMap.js ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ ZipCodeMap)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _babel_runtime_helpers_esm_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/common/Component */ "flarum/common/Component");
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_common_Component__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! flarum/common/components/LoadingIndicator */ "flarum/common/components/LoadingIndicator");
/* harmony import */ var flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var external_load__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! external-load */ "./node_modules/external-load/index.js");







var addedResources = false;
var addResources = /*#__PURE__*/function () {
  var _ref = (0,_babel_runtime_helpers_esm_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1__["default"])( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_2___default().mark(function _callee() {
    return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_2___default().wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            if (!addedResources) {
              _context.next = 2;
              break;
            }
            return _context.abrupt("return");
          case 2:
            _context.next = 4;
            return external_load__WEBPACK_IMPORTED_MODULE_6__["default"].css('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
          case 4:
            _context.next = 6;
            return external_load__WEBPACK_IMPORTED_MODULE_6__["default"].js('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js');
          case 6:
            addedResources = true;
          case 7:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return function addResources() {
    return _ref.apply(this, arguments);
  };
}();
var ZipCodeMap = /*#__PURE__*/function (_Component) {
  (0,_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(ZipCodeMap, _Component);
  function ZipCodeMap() {
    return _Component.apply(this, arguments) || this;
  }
  var _proto = ZipCodeMap.prototype;
  _proto.oninit = function oninit(vnode) {
    _Component.prototype.oninit.call(this, vnode);
    this.ipInfo = this.attrs.ipInfo;
    this.data = null;
    if (this.ipInfo.zipCode()) {
      this.searchZip();
    } else {
      this.searchLatLon();
    }
  };
  _proto.view = function view() {
    if (this.loading) {
      return m((flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_5___default()), {
        size: "medium"
      });
    } else if (!this.data) {
      return m("div", null);
    }
    return m("div", {
      id: "geoip-map",
      oncreate: this.configMap.bind(this)
    });
  };
  _proto.searchZip = function searchZip() {
    var _this = this;
    if (this.loading) return;
    this.loading = true;
    return addResources().then(flarum_forum_app__WEBPACK_IMPORTED_MODULE_3___default().request({
      url: "https://nominatim.openstreetmap.org/search",
      method: 'GET',
      params: {
        q: this.ipInfo.zipCode(),
        countrycodes: this.ipInfo.countryCode(),
        limit: 1,
        format: 'json'
      }
    }).then(function (data) {
      _this.data = data[0];
      _this.loading = false;
      m.redraw();
    }));
  };
  _proto.searchLatLon = function searchLatLon() {
    var _this2 = this;
    if (this.loading) return;
    this.loading = true;
    return addResources().then(flarum_forum_app__WEBPACK_IMPORTED_MODULE_3___default().request({
      url: "https://nominatim.openstreetmap.org/reverse",
      method: 'GET',
      params: {
        lat: this.ipInfo.latitude(),
        lon: this.ipInfo.longitude(),
        format: 'json'
      }
    }).then(function (data) {
      _this2.data = data;
      _this2.loading = false;
      m.redraw();
    }));
  };
  _proto.configMap = function configMap(vnode) {
    if (!this.data) return;
    var _this$data = this.data,
      bounding = _this$data.boundingbox,
      displayName = _this$data.display_name;
    this.map = L.map(vnode.dom).setView([51.505, -0.09], 5);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(this.map);
    L.marker([bounding[0], bounding[2]]).addTo(this.map).bindPopup(displayName).openPopup();
  };
  return ZipCodeMap;
}((flarum_common_Component__WEBPACK_IMPORTED_MODULE_4___default()));


/***/ }),

/***/ "./src/forum/extend.ts":
/*!*****************************!*\
  !*** ./src/forum/extend.ts ***!
  \*****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var flarum_common_extenders__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/common/extenders */ "flarum/common/extenders");
/* harmony import */ var flarum_common_extenders__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extenders__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _models_IPInfo__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./models/IPInfo */ "./src/forum/models/IPInfo.ts");
/* harmony import */ var flarum_common_models_Post__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/models/Post */ "flarum/common/models/Post");
/* harmony import */ var flarum_common_models_Post__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_models_Post__WEBPACK_IMPORTED_MODULE_2__);



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ([new (flarum_common_extenders__WEBPACK_IMPORTED_MODULE_0___default().Store)() //
.add('ip_info', _models_IPInfo__WEBPACK_IMPORTED_MODULE_1__["default"]), new (flarum_common_extenders__WEBPACK_IMPORTED_MODULE_0___default().Model)((flarum_common_models_Post__WEBPACK_IMPORTED_MODULE_2___default())) //
.hasOne('ip_info')]);

/***/ }),

/***/ "./src/forum/extenders/extendAccessTokensList.tsx":
/*!********************************************************!*\
  !*** ./src/forum/extenders/extendAccessTokensList.tsx ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ extendAccessTokensList)
/* harmony export */ });
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/extend */ "flarum/common/extend");
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_forum_components_AccessTokensList__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/forum/components/AccessTokensList */ "flarum/forum/components/AccessTokensList");
/* harmony import */ var flarum_forum_components_AccessTokensList__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_components_AccessTokensList__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _components_MapModal__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../components/MapModal */ "./src/forum/components/MapModal.tsx");
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/common/components/Button */ "flarum/common/components/Button");
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_4__);





function extendAccessTokensList() {
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)((flarum_forum_components_AccessTokensList__WEBPACK_IMPORTED_MODULE_2___default().prototype), 'tokenActionItems', function (items, token) {
    var ipAddr = token.lastIpAddress();
    if (ipAddr) {
      items.add('geoip-info', m((flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_4___default()), {
        className: "Button",
        onclick: function onclick() {
          return flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().modal.show(_components_MapModal__WEBPACK_IMPORTED_MODULE_3__["default"], {
            ipAddr: ipAddr
          });
        },
        "aria-label": flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-geoip.forum.map_button_label')
      }, flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-geoip.forum.map_button_label')), 10);
    }
  });
}

/***/ }),

/***/ "./src/forum/extenders/extendBanIPModal.js":
/*!*************************************************!*\
  !*** ./src/forum/extenders/extendBanIPModal.js ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ extendBanIPModal)
/* harmony export */ });
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/extend */ "flarum/common/extend");
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _components_ZipCodeMap__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../components/ZipCodeMap */ "./src/forum/components/ZipCodeMap.js");
/* harmony import */ var flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/components/Tooltip */ "flarum/common/components/Tooltip");
/* harmony import */ var flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _helpers_IPDataHelper__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../helpers/IPDataHelper */ "./src/forum/helpers/IPDataHelper.tsx");
/* harmony import */ var _helpers_ClipboardHelper__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../helpers/ClipboardHelper */ "./src/forum/helpers/ClipboardHelper.ts");
function _createForOfIteratorHelperLoose(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (it) return (it = it.call(o)).next.bind(it); if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; return function () { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }






function extendBanIPModal() {
  var BanIPModal = flarum.core.compat['fof/ban-ips/components/BanIPModal'];
  if (BanIPModal) {
    (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)(BanIPModal.prototype, 'content', function (vdom) {
      if (!this.post || !this.post.ipAddress()) return;
      var ipInfo = this.post.ip_info();
      var formGroup = vdom.children.find(function (e) {
        var _e$attrs, _e$attrs$className, _e$children;
        return (e == null ? void 0 : (_e$attrs = e.attrs) == null ? void 0 : (_e$attrs$className = _e$attrs.className) == null ? void 0 : _e$attrs$className.includes('Form-group')) && ((_e$children = e.children) == null ? void 0 : _e$children.find == null ? void 0 : _e$children.find(function (e) {
          return e.tag === 'div';
        }));
      });
      if (!ipInfo || !formGroup) return;
      for (var _iterator = _createForOfIteratorHelperLoose(formGroup.children), _step; !(_step = _iterator()).done;) {
        var child = _step.value;
        var label = child.children.find(function (e) {
          return (e == null ? void 0 : e.tag) === 'label';
        });
        var code = label && label.children.find(function (e) {
          return (e == null ? void 0 : e.tag) === 'code';
        });
        var codeIndex = code && label.children.indexOf(code);
        if (!code) continue;
        var _getIPData = (0,_helpers_IPDataHelper__WEBPACK_IMPORTED_MODULE_4__.getIPData)(ipInfo),
          description = _getIPData.description,
          threat = _getIPData.threat,
          image = _getIPData.image;
        if (!code.attrs) code.attrs = {};
        code.attrs['data-threat-level'] = ipInfo.threatLevel();
        code.children[1] = m((flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_3___default()), {
          text: description + (!!threat ? " (" + threat + ")" : '')
        }, m("span", null, code.children[1]));
        if (image) {
          label.children.splice(codeIndex, 0, image);
        }
      }
      if (ipInfo.zipCode() && ipInfo.countryCode()) {
        vdom.children.splice(2, 0, m("div", {
          className: "Form-group"
        }, m(_components_ZipCodeMap__WEBPACK_IMPORTED_MODULE_2__["default"], {
          zip: ipInfo.zipCode(),
          country: ipInfo.countryCode()
        })));
      }
    });
  }
}

/***/ }),

/***/ "./src/forum/extenders/extendPostMeta.js":
/*!***********************************************!*\
  !*** ./src/forum/extenders/extendPostMeta.js ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ extendPostMeta)
/* harmony export */ });
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/extend */ "flarum/common/extend");
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_components_PostMeta__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/components/PostMeta */ "flarum/common/components/PostMeta");
/* harmony import */ var flarum_common_components_PostMeta__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_PostMeta__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/components/Tooltip */ "flarum/common/components/Tooltip");
/* harmony import */ var flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _helpers_IPDataHelper__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../helpers/IPDataHelper */ "./src/forum/helpers/IPDataHelper.tsx");
/* harmony import */ var _helpers_ClipboardHelper__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../helpers/ClipboardHelper */ "./src/forum/helpers/ClipboardHelper.ts");
/* harmony import */ var flarum_common_utils_ItemList__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! flarum/common/utils/ItemList */ "flarum/common/utils/ItemList");
/* harmony import */ var flarum_common_utils_ItemList__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_ItemList__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! flarum/common/components/Button */ "flarum/common/components/Button");
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _components_MapModal__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../components/MapModal */ "./src/forum/components/MapModal.tsx");









function extendPostMeta() {
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)((flarum_common_components_PostMeta__WEBPACK_IMPORTED_MODULE_2___default().prototype), 'view', function (vdom) {
    var post = this.attrs.post;

    // Exit early if there's no post
    if (!post) return;
    var ipInformation = post.ip_info();

    // Exit early if there's no IP information for the post
    if (!ipInformation) return;

    // Extract dropdown from the VDOM
    var menuDropdown = vdom.children.find(function (e) {
      var _e$attrs, _e$attrs$className;
      return (_e$attrs = e.attrs) == null ? void 0 : (_e$attrs$className = _e$attrs.className) == null ? void 0 : _e$attrs$className.includes('dropdown-menu');
    });

    // Extract IP element for modification
    var ipElement = menuDropdown.children.find(function (e) {
      var _e$attrs2;
      return e.tag === 'span' && ((_e$attrs2 = e.attrs) == null ? void 0 : _e$attrs2.className) === 'PostMeta-ip';
    });

    // Clear any default text from the IP element
    delete ipElement.text;

    // Construct the IP element with the tooltip and interactive behavior
    ipElement.children = [m("div", {
      className: "ip-container"
    }, this.ipItems().toArray())];

    // If there's a threat level, add it as a data attribute for potential styling
    // TODO: move this to an Item?
    if (ipInformation.threatLevel) {
      ipElement.attrs['data-threat-level'] = ipInformation.threatLevel();
    }
  });
  (flarum_common_components_PostMeta__WEBPACK_IMPORTED_MODULE_2___default().prototype).ipItems = function () {
    var items = new (flarum_common_utils_ItemList__WEBPACK_IMPORTED_MODULE_6___default())();
    var post = this.attrs.post;
    var ipInformation = post.ip_info();
    var ipAddr = post.data.attributes.ipAddress;
    if (ipInformation && ipAddr) {
      var _getIPData = (0,_helpers_IPDataHelper__WEBPACK_IMPORTED_MODULE_4__.getIPData)(ipInformation),
        description = _getIPData.description,
        threat = _getIPData.threat,
        image = _getIPData.image,
        zip = _getIPData.zip,
        country = _getIPData.country;
      items.add('ipInfo', m("div", {
        className: "ip-info"
      }, image, m((flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_3___default()), {
        text: description + " " + (threat ? "(" + threat + ")" : '')
      }, m("span", {
        onclick: (0,_helpers_ClipboardHelper__WEBPACK_IMPORTED_MODULE_5__.handleCopyIP)(ipAddr)
      }, ipAddr))), 100);
      if (zip && country) {
        items.add('mapButton', m((flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_3___default()), {
          text: flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-geoip.forum.map_button_label')
        }, m((flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_7___default()), {
          icon: "fas fa-map-marker-alt",
          className: "Button Button--icon Button--link",
          onclick: function onclick() {
            return flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().modal.show(_components_MapModal__WEBPACK_IMPORTED_MODULE_8__["default"], {
              ipInfo: ipInformation,
              ipAddr: ipAddr
            });
          },
          "aria-label": flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-geoip.forum.map_button_label')
        })), 90);
      }
    }
    return items;
  };
}

/***/ }),

/***/ "./src/forum/helpers/ClipboardHelper.ts":
/*!**********************************************!*\
  !*** ./src/forum/helpers/ClipboardHelper.ts ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   handleCopyIP: () => (/* binding */ handleCopyIP)
/* harmony export */ });
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _util_copyToClipboard__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../util/copyToClipboard */ "./src/forum/util/copyToClipboard.ts");


var handleCopyIP = function handleCopyIP(ip) {
  return function () {
    (0,_util_copyToClipboard__WEBPACK_IMPORTED_MODULE_1__["default"])(ip);
    flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().alerts.show({
      type: 'success'
    }, flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-geoip.forum.alerts.ip_copied'));
  };
};

/***/ }),

/***/ "./src/forum/helpers/IPDataHelper.tsx":
/*!********************************************!*\
  !*** ./src/forum/helpers/IPDataHelper.tsx ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getDescription: () => (/* binding */ getDescription),
/* harmony export */   getFlagImage: () => (/* binding */ getFlagImage),
/* harmony export */   getIPData: () => (/* binding */ getIPData),
/* harmony export */   getThreat: () => (/* binding */ getThreat)
/* harmony export */ });
/* harmony import */ var _util_getFlagEmojiUrl__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../util/getFlagEmojiUrl */ "./src/forum/util/getFlagEmojiUrl.js");
/* harmony import */ var flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/components/Tooltip */ "flarum/common/components/Tooltip");
/* harmony import */ var flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_1__);


var getDescription = function getDescription(ipInfo) {
  return ipInfo.organization() || ipInfo.isp() || ipInfo.error() || '';
};
var getThreat = function getThreat(ipInfo) {
  return ipInfo.threatTypes() && ipInfo.threatTypes().join(', ');
};
var getFlagImage = function getFlagImage(ipInfo) {
  if (ipInfo.countryCode()) {
    var url = (0,_util_getFlagEmojiUrl__WEBPACK_IMPORTED_MODULE_0__["default"])(ipInfo.countryCode());
    if (url) {
      return m((flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_1___default()), {
        text: ipInfo.countryCode()
      }, m("img", {
        src: url,
        alt: ipInfo.countryCode(),
        height: "16",
        loading: "lazy"
      }));
    }
  }
  return null;
};
var getIPData = function getIPData(ipInfo) {
  var description = getDescription(ipInfo);
  var threat = getThreat(ipInfo);
  var image = getFlagImage(ipInfo);

  // Extracting zip and country from ipInfo
  var zip = ipInfo.zipCode();
  var country = ipInfo.countryCode(); // Assuming the country code is used as 'country'

  return {
    description: description,
    threat: threat,
    image: image,
    zip: zip,
    country: country
  };
};

/***/ }),

/***/ "./src/forum/index.ts":
/*!****************************!*\
  !*** ./src/forum/index.ts ***!
  \****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   extend: () => (/* reexport safe */ _extend__WEBPACK_IMPORTED_MODULE_4__["default"])
/* harmony export */ });
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _extenders_extendPostMeta__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./extenders/extendPostMeta */ "./src/forum/extenders/extendPostMeta.js");
/* harmony import */ var _extenders_extendBanIPModal__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./extenders/extendBanIPModal */ "./src/forum/extenders/extendBanIPModal.js");
/* harmony import */ var _extenders_extendAccessTokensList__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./extenders/extendAccessTokensList */ "./src/forum/extenders/extendAccessTokensList.tsx");
/* harmony import */ var _extend__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./extend */ "./src/forum/extend.ts");





flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().initializers.add('fof/geoip', function () {
  (0,_extenders_extendPostMeta__WEBPACK_IMPORTED_MODULE_1__["default"])();
  (0,_extenders_extendBanIPModal__WEBPACK_IMPORTED_MODULE_2__["default"])();
  (0,_extenders_extendAccessTokensList__WEBPACK_IMPORTED_MODULE_3__["default"])();
});

/***/ }),

/***/ "./src/forum/models/IPInfo.ts":
/*!************************************!*\
  !*** ./src/forum/models/IPInfo.ts ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ IPInfo)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var flarum_common_Model__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/Model */ "flarum/common/Model");
/* harmony import */ var flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_Model__WEBPACK_IMPORTED_MODULE_1__);


var IPInfo = /*#__PURE__*/function (_Model) {
  (0,_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(IPInfo, _Model);
  function IPInfo() {
    return _Model.apply(this, arguments) || this;
  }
  var _proto = IPInfo.prototype;
  _proto.id = function id() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default().attribute('id').call(this);
  };
  _proto.countryCode = function countryCode() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default().attribute('countryCode').call(this);
  };
  _proto.zipCode = function zipCode() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default().attribute('zipCode').call(this);
  };
  _proto.latitude = function latitude() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default().attribute('latitude').call(this);
  };
  _proto.longitude = function longitude() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default().attribute('longitude').call(this);
  };
  _proto.isp = function isp() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default().attribute('isp').call(this);
  };
  _proto.organization = function organization() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default().attribute('organization').call(this);
  };
  _proto.as = function as() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default().attribute('as').call(this);
  };
  _proto.mobile = function mobile() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default().attribute('mobile').call(this);
  };
  _proto.threatLevel = function threatLevel() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default().attribute('threatLevel').call(this);
  };
  _proto.threatTypes = function threatTypes() {
    var raw = flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default().attribute('threatTypes').call(this);
    try {
      return JSON.parse(raw);
    } catch (error) {
      return [];
    }
  };
  _proto.error = function error() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default().attribute('error').call(this);
  };
  _proto.dataProvider = function dataProvider() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default().attribute('dataProvider').call(this);
  };
  _proto.createdAt = function createdAt() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default().attribute('createdAt', (flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default().transformDate)).call(this);
  };
  _proto.updatedAt = function updatedAt() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default().attribute('updatedAt', (flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default().transformDate)).call(this);
  };
  return IPInfo;
}((flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default()));


/***/ }),

/***/ "./src/forum/util/copyToClipboard.ts":
/*!*******************************************!*\
  !*** ./src/forum/util/copyToClipboard.ts ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (function (str) {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(str).then(function () {});
    return;
  }

  // Fallback method:
  var el = document.createElement('textarea');
  el.value = str;
  el.setAttribute('readonly', '');
  el.style.position = 'absolute';
  el.style.left = '-9999px';
  document.body.appendChild(el);
  var selection = document.getSelection();
  var selected = selection && selection.rangeCount > 0 ? selection.getRangeAt(0) : null;
  el.select();
  document.execCommand('copy');
  document.body.removeChild(el);
  if (selected && selection) {
    selection.removeAllRanges();
    selection.addRange(selected);
  }
});

/***/ }),

/***/ "./src/forum/util/getFlagEmojiUrl.js":
/*!*******************************************!*\
  !*** ./src/forum/util/getFlagEmojiUrl.js ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var country_code_emoji__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! country-code-emoji */ "./node_modules/country-code-emoji/lib/index.umd.js");
/* harmony import */ var country_code_emoji__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(country_code_emoji__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var twemoji_basename__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! twemoji-basename */ "./node_modules/twemoji-basename/index.js");
/* harmony import */ var twemoji_basename__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(twemoji_basename__WEBPACK_IMPORTED_MODULE_1__);


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (function (countryCode) {
  var codepoint = country_code_emoji__WEBPACK_IMPORTED_MODULE_0___default()(countryCode);
  if (!codepoint) return null;
  var basename = twemoji_basename__WEBPACK_IMPORTED_MODULE_1___default()(codepoint);
  return basename ? "https://cdn.jsdelivr.net/gh/twitter/twemoji@14/assets/72x72/" + basename + ".png" : null;
});

/***/ }),

/***/ "flarum/common/Component":
/*!*********************************************************!*\
  !*** external "flarum.core.compat['common/Component']" ***!
  \*********************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/Component'];

/***/ }),

/***/ "flarum/common/Model":
/*!*****************************************************!*\
  !*** external "flarum.core.compat['common/Model']" ***!
  \*****************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/Model'];

/***/ }),

/***/ "flarum/common/components/Button":
/*!*****************************************************************!*\
  !*** external "flarum.core.compat['common/components/Button']" ***!
  \*****************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/components/Button'];

/***/ }),

/***/ "flarum/common/components/LabelValue":
/*!*********************************************************************!*\
  !*** external "flarum.core.compat['common/components/LabelValue']" ***!
  \*********************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/components/LabelValue'];

/***/ }),

/***/ "flarum/common/components/LoadingIndicator":
/*!***************************************************************************!*\
  !*** external "flarum.core.compat['common/components/LoadingIndicator']" ***!
  \***************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/components/LoadingIndicator'];

/***/ }),

/***/ "flarum/common/components/Modal":
/*!****************************************************************!*\
  !*** external "flarum.core.compat['common/components/Modal']" ***!
  \****************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/components/Modal'];

/***/ }),

/***/ "flarum/common/components/PostMeta":
/*!*******************************************************************!*\
  !*** external "flarum.core.compat['common/components/PostMeta']" ***!
  \*******************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/components/PostMeta'];

/***/ }),

/***/ "flarum/common/components/Tooltip":
/*!******************************************************************!*\
  !*** external "flarum.core.compat['common/components/Tooltip']" ***!
  \******************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/components/Tooltip'];

/***/ }),

/***/ "flarum/common/extend":
/*!******************************************************!*\
  !*** external "flarum.core.compat['common/extend']" ***!
  \******************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/extend'];

/***/ }),

/***/ "flarum/common/extenders":
/*!*********************************************************!*\
  !*** external "flarum.core.compat['common/extenders']" ***!
  \*********************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/extenders'];

/***/ }),

/***/ "flarum/common/models/Post":
/*!***********************************************************!*\
  !*** external "flarum.core.compat['common/models/Post']" ***!
  \***********************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/models/Post'];

/***/ }),

/***/ "flarum/common/utils/ItemList":
/*!**************************************************************!*\
  !*** external "flarum.core.compat['common/utils/ItemList']" ***!
  \**************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/utils/ItemList'];

/***/ }),

/***/ "flarum/forum/app":
/*!**************************************************!*\
  !*** external "flarum.core.compat['forum/app']" ***!
  \**************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['forum/app'];

/***/ }),

/***/ "flarum/forum/components/AccessTokensList":
/*!**************************************************************************!*\
  !*** external "flarum.core.compat['forum/components/AccessTokensList']" ***!
  \**************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['forum/components/AccessTokensList'];

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _asyncToGenerator)
/* harmony export */ });
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) {
  try {
    var info = gen[key](arg);
    var value = info.value;
  } catch (error) {
    reject(error);
    return;
  }
  if (info.done) {
    resolve(value);
  } else {
    Promise.resolve(value).then(_next, _throw);
  }
}
function _asyncToGenerator(fn) {
  return function () {
    var self = this,
      args = arguments;
    return new Promise(function (resolve, reject) {
      var gen = fn.apply(self, args);
      function _next(value) {
        asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value);
      }
      function _throw(err) {
        asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err);
      }
      _next(undefined);
    });
  };
}

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js":
/*!******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js ***!
  \******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _inheritsLoose)
/* harmony export */ });
/* harmony import */ var _setPrototypeOf_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./setPrototypeOf.js */ "./node_modules/@babel/runtime/helpers/esm/setPrototypeOf.js");

function _inheritsLoose(subClass, superClass) {
  subClass.prototype = Object.create(superClass.prototype);
  subClass.prototype.constructor = subClass;
  (0,_setPrototypeOf_js__WEBPACK_IMPORTED_MODULE_0__["default"])(subClass, superClass);
}

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/setPrototypeOf.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/setPrototypeOf.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _setPrototypeOf)
/* harmony export */ });
function _setPrototypeOf(o, p) {
  _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) {
    o.__proto__ = p;
    return o;
  };
  return _setPrototypeOf(o, p);
}

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
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
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be in strict mode.
(() => {
"use strict";
/*!******************!*\
  !*** ./forum.js ***!
  \******************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   extend: () => (/* reexport safe */ _src_forum__WEBPACK_IMPORTED_MODULE_0__.extend)
/* harmony export */ });
/* harmony import */ var _src_forum__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./src/forum */ "./src/forum/index.ts");

})();

module.exports = __webpack_exports__;
/******/ })()
;
//# sourceMappingURL=forum.js.map