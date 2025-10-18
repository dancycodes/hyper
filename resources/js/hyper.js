const ResponseInterceptor = {
  type: "watcher",
  name: "responseInterceptor",
  onGlobalInit: () => {
    const originalFetch = window.fetch;
    window.fetch = async (...args) => {
      const [resource, init] = args;
      const requestUrl = getUrlFromResource(resource);
      if (shouldSkipUrl(requestUrl)) {
        return originalFetch(...args);
      }
      const isDatastarRequest = init?.headers?.["Datastar-Request"] === "true" || init?.headers?.get?.("Datastar-Request") === "true";
      try {
        const response = await originalFetch(...args);
        if (isRedirectResponse(response, requestUrl)) {
          const redirectUrl = getRedirectUrl(response);
          if (redirectUrl && redirectUrl !== window.location.href) {
            window.location.href = redirectUrl;
            return new Response("", { status: 204 });
          }
        }
        if (isDatastarRequest) {
          return response;
        }
        const isHyperResponse = response.headers.get("X-Hyper-Response") === "true";
        const isSSEResponse = response.headers.get("Content-Type")?.includes("text/event-stream");
        const hasDatastarEvent = response.headers.get("Content-Type")?.includes("text/event-stream") && response.url.includes("datastar") || response.headers.get("event")?.startsWith("datastar-");
        if (isHyperResponse || isSSEResponse || hasDatastarEvent) {
          return response;
        }
        try {
          await handleLaravelResponse(response, requestUrl);
        } catch (error) {
          if (error instanceof RedirectHandled) {
            return new Response("", { status: 204 });
          }
          throw error;
        }
        return new Response("", { status: 200 });
      } catch (error) {
        if (!(error instanceof RedirectHandled)) {
          console.error("Network error:", error);
        }
        throw error;
      }
    };
  }
};
class RedirectHandled extends Error {
  constructor() {
    super("Redirect handled");
    this.name = "RedirectHandled";
  }
}
function getUrlFromResource(resource) {
  if (typeof resource === "string") return resource;
  if (resource instanceof URL) return resource.href;
  if (resource instanceof Request) return resource.url;
  return String(resource);
}
function shouldSkipUrl(url2) {
  const skipPatterns = [
    "_boost/browser-logs",
    "_boost/",
    "_debugbar/",
    "_ignition/",
    "telescope/",
    "horizon/",
    ".js",
    ".css",
    ".png",
    ".jpg",
    ".jpeg",
    ".gif",
    ".svg",
    ".ico",
    ".woff",
    ".woff2",
    ".ttf",
    ".eot"
  ];
  return skipPatterns.some((pattern) => url2.includes(pattern));
}
async function handleLaravelResponse(response, requestUrl) {
  if (isRedirectResponse(response, requestUrl)) {
    const redirectUrl = getRedirectUrl(response);
    if (redirectUrl && redirectUrl !== window.location.href) {
      window.location.replace(redirectUrl);
      throw new RedirectHandled();
    }
    return;
  }
  if (response.status >= 400) {
    const html = await response.text();
    if (isLaravelSpecialResponse(html)) {
      replaceDocument(html);
    }
    return;
  }
  const contentType = response.headers.get("Content-Type") || "";
  if (contentType.includes("text/html")) {
    const html = await response.text();
    if (isLaravelSpecialResponse(html)) {
      replaceDocument(html);
    }
  }
}
function isRedirectResponse(response, originalUrl) {
  return [301, 302, 303, 307, 308].includes(response.status) || response.url && response.url !== originalUrl || !!response.headers.get("Location");
}
function getRedirectUrl(response) {
  let redirectUrl = response.url || response.headers.get("Location");
  if (!redirectUrl) return null;
  if (redirectUrl.startsWith("/")) {
    redirectUrl = window.location.origin + redirectUrl;
  }
  return redirectUrl;
}
function isLaravelSpecialResponse(html) {
  if (html.includes("ignition-") || html.includes("data-ignition") || html.includes("Whoops\\") || html.includes("whoops-container") || html.includes("Illuminate\\") && html.includes("Exception")) {
    return true;
  }
  if (html.includes("sf-dump") || html.includes("var-dump") || html.includes("symfony-var-dumper") || html.includes("dump-container")) {
    return true;
  }
  return false;
}
function replaceDocument(html) {
  try {
    const parser = new DOMParser();
    const newDoc = parser.parseFromString(html, "text/html");
    document.documentElement.innerHTML = newDoc.documentElement.innerHTML;
    if (newDoc.title) {
      document.title = newDoc.title;
    }
    executeNewScripts();
  } catch (error) {
    console.error("Failed to replace document:", error);
    window.location.reload();
  }
}
function executeNewScripts() {
  const scripts2 = document.querySelectorAll("script:not([data-executed])");
  scripts2.forEach((element) => {
    const oldScript = element;
    oldScript.setAttribute("data-executed", "true");
    const newScript = document.createElement("script");
    Array.from(oldScript.attributes).forEach((attr) => {
      newScript.setAttribute(attr.name, attr.value);
    });
    if (oldScript.src) {
      newScript.src = oldScript.src;
    } else {
      newScript.textContent = oldScript.textContent;
    }
    if (oldScript.parentNode) {
      oldScript.parentNode.replaceChild(newScript, oldScript);
    }
  });
}
const lol = /🖕JS_DS🚀/.source;
const DSP = lol.slice(0, 5);
const DSS = lol.slice(4);
const DATASTAR = "datastar";
const DATASTAR_REQUEST = "Datastar-Request";
const DefaultSseRetryDurationMs = 1e3;
const DefaultPatchSignalsOnlyIfMissing = false;
const ElementPatchModeOuter = "outer";
const ElementPatchModeInner = "inner";
const ElementPatchModeRemove = "remove";
const ElementPatchModeReplace = "replace";
const ElementPatchModePrepend = "prepend";
const ElementPatchModeAppend = "append";
const ElementPatchModeBefore = "before";
const ElementPatchModeAfter = "after";
const DefaultElementPatchMode = ElementPatchModeOuter;
const EventTypePatchElements = "datastar-patch-elements";
const EventTypePatchSignals = "datastar-patch-signals";
function isHTMLOrSVG(el) {
  return el instanceof HTMLElement || el instanceof SVGElement;
}
const isPojo = (obj) => obj !== null && typeof obj === "object" && (Object.getPrototypeOf(obj) === Object.prototype || Object.getPrototypeOf(obj) === null);
function isEmpty(obj) {
  for (const prop in obj) {
    if (Object.hasOwn(obj, prop)) {
      return false;
    }
  }
  return true;
}
function updateLeaves(obj, fn) {
  for (const key in obj) {
    const val = obj[key];
    if (isPojo(val) || Array.isArray(val)) {
      updateLeaves(val, fn);
    } else {
      obj[key] = fn(val);
    }
  }
}
const pathToObj = (target, paths) => {
  for (const path in paths) {
    const keys = path.split(".");
    const lastKey = keys.pop();
    const obj = keys.reduce((acc, key) => acc[key] ??= {}, target);
    obj[lastKey] = paths[path];
  }
  return target;
};
const isBoolString = (str) => str.trim() === "true";
const kebab = (str) => str.replace(/([a-z0-9])([A-Z])/g, "$1-$2").replace(/([a-z])([0-9]+)/gi, "$1-$2").replace(/([0-9]+)([a-z])/gi, "$1-$2").toLowerCase();
const camel = (str) => kebab(str).replace(/-./g, (x) => x[1].toUpperCase());
const snake = (str) => kebab(str).replace(/-/g, "_");
const pascal = (str) => camel(str).replace(new RegExp("(^.|(?<=\\.).)", "g"), (x) => x[0].toUpperCase());
const jsStrToObject = (raw) => {
  try {
    return JSON.parse(raw);
  } catch {
    return Function(`return (${raw})`)();
  }
};
const caseFns = { kebab, snake, pascal };
function modifyCasing(str, mods) {
  for (const c of mods.get("case") || []) {
    const fn = caseFns[c];
    if (fn) str = fn(str);
  }
  return str;
}
const url = "https://data-star.dev/errors";
function dserr(type, reason, metadata = {}) {
  const e = new Error();
  e.name = `${DATASTAR} ${type} error`;
  const r = snake(reason);
  const q = new URLSearchParams({
    metadata: JSON.stringify(metadata)
  }).toString();
  const c = JSON.stringify(metadata, null, 2);
  e.message = `${reason}
More info: ${url}/${type}/${r}?${q}
Context: ${c}`;
  return e;
}
function initErr(ctx, reason, metadata = {}) {
  const errCtx = {
    plugin: {
      name: ctx.plugin.name,
      type: ctx.plugin.type
    }
  };
  return dserr("init", reason, Object.assign(errCtx, metadata));
}
function runtimeErr(ctx, reason, metadata = {}) {
  const errCtx = {
    plugin: {
      name: ctx.plugin.name,
      type: ctx.plugin.type
    },
    element: {
      id: ctx.el.id,
      tag: ctx.el.tagName
    },
    expression: {
      rawKey: ctx.rawKey,
      key: ctx.key,
      value: ctx.value,
      // validSignals:
      fnContent: ctx.fnContent
    }
  };
  return dserr("runtime", reason, Object.assign(errCtx, metadata));
}
const DATASTAR_SIGNAL_PATCH_EVENT = `${DATASTAR}-signal-patch`;
let currentPatch = {};
const queuedEffects = [];
let batchDepth = 0;
let notifyIndex = 0;
let queuedEffectsLength = 0;
let activeSub;
const startBatch = () => {
  batchDepth++;
};
const endBatch = () => {
  if (!--batchDepth) {
    flush();
    dispatch();
  }
};
const signal = (initialValue) => {
  return signalOper.bind(0, {
    previousValue: initialValue,
    value_: initialValue,
    flags_: 1
  });
};
const computedSymbol = Symbol("computed");
const computed = (getter) => {
  const c = computedOper.bind(0, {
    flags_: 17,
    getter
  });
  c[computedSymbol] = 1;
  return c;
};
const effect = (fn) => {
  const e = {
    fn_: fn,
    flags_: 2
  };
  if (activeSub) {
    link(e, activeSub);
  }
  const prev = setCurrentSub(e);
  startBatch();
  try {
    e.fn_();
  } finally {
    endBatch();
    setCurrentSub(prev);
  }
  return effectOper.bind(0, e);
};
const peek = (fn) => {
  const prev = setCurrentSub(void 0);
  try {
    return fn();
  } finally {
    setCurrentSub(prev);
  }
};
const flush = () => {
  while (notifyIndex < queuedEffectsLength) {
    const effect2 = queuedEffects[notifyIndex];
    queuedEffects[notifyIndex++] = void 0;
    run(
      effect2,
      effect2.flags_ &= -65
      /* Queued */
    );
  }
  notifyIndex = 0;
  queuedEffectsLength = 0;
};
const update = (signal2) => {
  if ("getter" in signal2) {
    return updateComputed(signal2);
  }
  return updateSignal(signal2, signal2.value_);
};
const setCurrentSub = (sub) => {
  const prevSub = activeSub;
  activeSub = sub;
  return prevSub;
};
const updateComputed = (c) => {
  const prevSub = setCurrentSub(c);
  startTracking(c);
  try {
    const oldValue = c.value_;
    return oldValue !== (c.value_ = c.getter(oldValue));
  } finally {
    setCurrentSub(prevSub);
    endTracking(c);
  }
};
const updateSignal = (s, value) => {
  s.flags_ = 1;
  return s.previousValue !== (s.previousValue = value);
};
const notify = (e) => {
  const flags = e.flags_;
  if (!(flags & 64)) {
    e.flags_ = flags | 64;
    const subs = e.subs_;
    if (subs) {
      notify(subs.sub_);
    } else {
      queuedEffects[queuedEffectsLength++] = e;
    }
  }
};
const run = (e, flags) => {
  if (flags & 16 || flags & 32 && checkDirty(e.deps_, e)) {
    const prev = setCurrentSub(e);
    startTracking(e);
    startBatch();
    try {
      e.fn_();
    } finally {
      endBatch();
      setCurrentSub(prev);
      endTracking(e);
    }
    return;
  }
  if (flags & 32) {
    e.flags_ = flags & -33;
  }
  let link2 = e.deps_;
  while (link2) {
    const dep = link2.dep_;
    const depFlags = dep.flags_;
    if (depFlags & 64) {
      run(
        dep,
        dep.flags_ = depFlags & -65
        /* Queued */
      );
    }
    link2 = link2.nextDep_;
  }
};
const computedOper = (c) => {
  const flags = c.flags_;
  if (flags & 16 || flags & 32 && checkDirty(c.deps_, c)) {
    if (updateComputed(c)) {
      const subs = c.subs_;
      if (subs) {
        shallowPropagate(subs);
      }
    }
  } else if (flags & 32) {
    c.flags_ = flags & -33;
  }
  if (activeSub) {
    link(c, activeSub);
  }
  return c.value_;
};
const signalOper = (s, ...value) => {
  if (value.length) {
    const newValue = value[0];
    if (s.value_ !== (s.value_ = newValue)) {
      s.flags_ = 17;
      const subs = s.subs_;
      if (subs) {
        propagate(subs);
        if (!batchDepth) {
          flush();
        }
      }
      return true;
    }
    return false;
  }
  const currentValue = s.value_;
  if (s.flags_ & 16) {
    if (updateSignal(s, currentValue)) {
      const subs_ = s.subs_;
      if (subs_) {
        shallowPropagate(subs_);
      }
    }
  }
  if (activeSub) {
    link(s, activeSub);
  }
  return currentValue;
};
const effectOper = (e) => {
  let dep = e.deps_;
  while (dep) {
    dep = unlink(dep, e);
  }
  const sub = e.subs_;
  if (sub) {
    unlink(sub);
  }
  e.flags_ = 0;
};
const link = (dep, sub) => {
  const prevDep = sub.depsTail_;
  if (prevDep && prevDep.dep_ === dep) {
    return;
  }
  let nextDep;
  const recursedCheck = sub.flags_ & 4;
  if (recursedCheck) {
    nextDep = prevDep ? prevDep.nextDep_ : sub.deps_;
    if (nextDep && nextDep.dep_ === dep) {
      sub.depsTail_ = nextDep;
      return;
    }
  }
  const prevSub = dep.subsTail_;
  if (prevSub && prevSub.sub_ === sub && (!recursedCheck || isValidLink(prevSub, sub))) {
    return;
  }
  const newLink = sub.depsTail_ = dep.subsTail_ = {
    dep_: dep,
    sub_: sub,
    prevDep_: prevDep,
    nextDep_: nextDep,
    prevSub_: prevSub
  };
  if (nextDep) {
    nextDep.prevDep_ = newLink;
  }
  if (prevDep) {
    prevDep.nextDep_ = newLink;
  } else {
    sub.deps_ = newLink;
  }
  if (prevSub) {
    prevSub.nextSub_ = newLink;
  } else {
    dep.subs_ = newLink;
  }
};
const unlink = (link2, sub_ = link2.sub_) => {
  const dep_ = link2.dep_;
  const prevDep_ = link2.prevDep_;
  const nextDep_ = link2.nextDep_;
  const nextSub_ = link2.nextSub_;
  const prevSub_ = link2.prevSub_;
  if (nextDep_) {
    nextDep_.prevDep_ = prevDep_;
  } else {
    sub_.depsTail_ = prevDep_;
  }
  if (prevDep_) {
    prevDep_.nextDep_ = nextDep_;
  } else {
    sub_.deps_ = nextDep_;
  }
  if (nextSub_) {
    nextSub_.prevSub_ = prevSub_;
  } else {
    dep_.subsTail_ = prevSub_;
  }
  if (prevSub_) {
    prevSub_.nextSub_ = nextSub_;
  } else if (!(dep_.subs_ = nextSub_)) {
    if ("getter" in dep_) {
      let toRemove = dep_.deps_;
      if (toRemove) {
        dep_.flags_ = 17;
        do {
          toRemove = unlink(toRemove, dep_);
        } while (toRemove);
      }
    } else if (!("previousValue" in dep_)) {
      effectOper(dep_);
    }
  }
  return nextDep_;
};
const propagate = (link2) => {
  let next = link2.nextSub_;
  let stack;
  top: while (true) {
    const sub = link2.sub_;
    let flags = sub.flags_;
    if (flags & 3) {
      if (!(flags & 60)) {
        sub.flags_ = flags | 32;
      } else if (!(flags & 12)) {
        flags = 0;
      } else if (!(flags & 4)) {
        sub.flags_ = flags & -9 | 32;
      } else if (!(flags & 48) && isValidLink(link2, sub)) {
        sub.flags_ = flags | 40;
        flags &= 1;
      } else {
        flags = 0;
      }
      if (flags & 2) {
        notify(sub);
      }
      if (flags & 1) {
        const subSubs = sub.subs_;
        if (subSubs) {
          link2 = subSubs;
          if (subSubs.nextSub_) {
            stack = { value_: next, prev_: stack };
            next = link2.nextSub_;
          }
          continue;
        }
      }
    }
    if (link2 = next) {
      next = link2.nextSub_;
      continue;
    }
    while (stack) {
      link2 = stack.value_;
      stack = stack.prev_;
      if (link2) {
        next = link2.nextSub_;
        continue top;
      }
    }
    break;
  }
};
const startTracking = (sub) => {
  sub.depsTail_ = void 0;
  sub.flags_ = sub.flags_ & -57 | 4;
};
const endTracking = (sub) => {
  const depsTail_ = sub.depsTail_;
  let toRemove = depsTail_ ? depsTail_.nextDep_ : sub.deps_;
  while (toRemove) {
    toRemove = unlink(toRemove, sub);
  }
  sub.flags_ &= -5;
};
const checkDirty = (link2, sub) => {
  let stack;
  let checkDepth = 0;
  top: while (true) {
    const dep = link2.dep_;
    const depFlags = dep.flags_;
    let dirty = false;
    if (sub.flags_ & 16) {
      dirty = true;
    } else if ((depFlags & 17) === 17) {
      if (update(dep)) {
        const subs = dep.subs_;
        if (subs.nextSub_) {
          shallowPropagate(subs);
        }
        dirty = true;
      }
    } else if ((depFlags & 33) === 33) {
      if (link2.nextSub_ || link2.prevSub_) {
        stack = { value_: link2, prev_: stack };
      }
      link2 = dep.deps_;
      sub = dep;
      ++checkDepth;
      continue;
    }
    if (!dirty && link2.nextDep_) {
      link2 = link2.nextDep_;
      continue;
    }
    while (checkDepth) {
      --checkDepth;
      const firstSub = sub.subs_;
      const hasMultipleSubs = firstSub.nextSub_;
      if (hasMultipleSubs) {
        link2 = stack.value_;
        stack = stack.prev_;
      } else {
        link2 = firstSub;
      }
      if (dirty) {
        if (update(sub)) {
          if (hasMultipleSubs) {
            shallowPropagate(firstSub);
          }
          sub = link2.sub_;
          continue;
        }
      } else {
        sub.flags_ &= -33;
      }
      sub = link2.sub_;
      if (link2.nextDep_) {
        link2 = link2.nextDep_;
        continue top;
      }
      dirty = false;
    }
    return dirty;
  }
};
const shallowPropagate = (link2) => {
  do {
    const sub = link2.sub_;
    const nextSub = link2.nextSub_;
    const subFlags = sub.flags_;
    if ((subFlags & 48) === 32) {
      sub.flags_ = subFlags | 16;
      if (subFlags & 2) {
        notify(sub);
      }
    }
    link2 = nextSub;
  } while (link2);
};
const isValidLink = (checkLink, sub) => {
  const depsTail = sub.depsTail_;
  if (depsTail) {
    let link2 = sub.deps_;
    do {
      if (link2 === checkLink) {
        return true;
      }
      if (link2 === depsTail) {
        break;
      }
      link2 = link2.nextDep_;
    } while (link2);
  }
  return false;
};
const getPath = (path) => {
  let result = root;
  const split = path.split(".");
  for (const path2 of split) {
    if (result == null || !Object.hasOwn(result, path2)) {
      return;
    }
    result = result[path2];
  }
  return result;
};
const DELETE$1 = Symbol("delete");
const deep = (value, prefix = "") => {
  const isArr = Array.isArray(value);
  if (isArr || isPojo(value)) {
    const deepObj = isArr ? [] : {};
    for (const key in value) {
      deepObj[key] = signal(
        deep(value[key], `${prefix + key}.`)
      );
    }
    const keys = signal(0);
    return new Proxy(deepObj, {
      get: (_, prop) => {
        if (!(prop === "toJSON" && !Object.hasOwn(deepObj, prop))) {
          if (isArr && prop in Array.prototype) {
            keys();
            return deepObj[prop];
          } else {
            if (typeof prop === "symbol") {
              return deepObj[prop];
            }
            if (!Object.hasOwn(deepObj, prop) || deepObj[prop]() == null) {
              deepObj[prop] = signal("");
              dispatch({ [prefix + prop]: "" });
              keys(keys() + 1);
            }
            return deepObj[prop]();
          }
        }
      },
      set: (_, prop, newValue) => {
        if (newValue === DELETE$1) {
          if (Object.hasOwn(deepObj, prop)) {
            delete deepObj[prop];
            dispatch({ [prefix + prop]: DELETE$1 });
            keys(keys() + 1);
          }
        } else {
          if (isArr && prop === "length") {
            deepObj[prop] = newValue;
            dispatch({ [prefix.slice(0, -1)]: deepObj });
            keys(keys() + 1);
          } else {
            if (Object.hasOwn(deepObj, prop)) {
              if (newValue == null) {
                if (deepObj[prop](null)) {
                  dispatch({ [prefix + prop]: null });
                }
              } else {
                if (Object.hasOwn(newValue, computedSymbol)) {
                  deepObj[prop] = newValue;
                  dispatch({ [prefix + prop]: "" });
                } else {
                  if (deepObj[prop](
                    deep(newValue, `${prefix + prop}.`)
                  )) {
                    dispatch({ [prefix + prop]: newValue });
                  }
                }
              }
            } else {
              if (newValue != null) {
                if (Object.hasOwn(newValue, computedSymbol)) {
                  deepObj[prop] = newValue;
                  dispatch({ [prefix + prop]: "" });
                } else {
                  deepObj[prop] = signal(
                    deep(newValue, `${prefix + prop}.`)
                  );
                  dispatch({ [prefix + prop]: newValue });
                }
                keys(keys() + 1);
              }
            }
          }
        }
        return true;
      },
      deleteProperty: (_, prop) => {
        if (Object.hasOwn(deepObj, prop)) {
          if (deepObj[prop](null)) {
            dispatch({ [prefix + prop]: null });
          }
        }
        return true;
      },
      ownKeys: () => {
        keys();
        return Reflect.ownKeys(deepObj);
      },
      has(_, prop) {
        keys();
        return prop in deepObj;
      }
    });
  }
  return value;
};
const dispatch = (obj) => {
  if (obj) {
    pathToObj(currentPatch, obj);
  }
  if (!batchDepth && !isEmpty(currentPatch)) {
    const oldPatch = currentPatch;
    currentPatch = {};
    document.dispatchEvent(
      new CustomEvent(DATASTAR_SIGNAL_PATCH_EVENT, {
        detail: oldPatch
      })
    );
  }
};
const mergePatch = (patch, { ifMissing } = {}) => {
  startBatch();
  for (const key in patch) {
    if (patch[key] == null) {
      if (!ifMissing) {
        delete root[key];
      }
    } else {
      mergeInner(patch[key], key, root, "", ifMissing);
    }
  }
  endBatch();
};
const mergeInner = (patch, target, targetParent, prefix, ifMissing) => {
  if (isPojo(patch)) {
    if (!(Object.hasOwn(targetParent, target) && (isPojo(targetParent[target]) || Array.isArray(targetParent[target])))) {
      targetParent[target] = {};
    }
    for (const key in patch) {
      if (patch[key] == null) {
        if (!ifMissing) {
          delete targetParent[target][key];
        }
      } else {
        mergeInner(
          patch[key],
          key,
          targetParent[target],
          `${prefix + target}.`,
          ifMissing
        );
      }
    }
  } else if (!(ifMissing && Object.hasOwn(targetParent, target))) {
    targetParent[target] = patch;
  }
};
function filtered({ include = /.*/, exclude = /(?!)/ } = {}, obj = root) {
  const pathObj = {};
  const stack = [[obj, ""]];
  while (stack.length) {
    const [node, prefix] = stack.pop();
    for (const key in node) {
      if (isPojo(node[key])) {
        stack.push([node[key], `${prefix + key}.`]);
      } else if (toRegExp(include).test(prefix + key) && !toRegExp(exclude).test(prefix + key)) {
        pathObj[prefix + key] = getPath(prefix + key);
      }
    }
  }
  return pathToObj({}, pathObj);
}
function toRegExp(val) {
  if (typeof val === "string") {
    return RegExp(val.replace(/^\/|\/$/g, ""));
  }
  return val;
}
const root = deep({});
const actions = {};
const plugins = [];
let pluginRegexs = [];
const removals = /* @__PURE__ */ new Map();
let mutationObserver = null;
let alias = "";
function setAlias(value) {
  alias = value;
}
function aliasify(name) {
  return alias ? `data-${alias}-${name}` : `data-${name}`;
}
function load(...pluginsToLoad) {
  for (const plugin of pluginsToLoad) {
    const ctx = {
      plugin,
      actions,
      root,
      filtered,
      signal,
      computed,
      effect,
      mergePatch,
      peek,
      getPath,
      startBatch,
      endBatch,
      initErr: 0
    };
    ctx.initErr = initErr.bind(0, ctx);
    if (plugin.type === "action") {
      actions[plugin.name] = plugin;
    } else if (plugin.type === "attribute") {
      plugins.push(plugin);
      plugin.onGlobalInit?.(ctx);
    } else if (plugin.type === "watcher") {
      plugin.onGlobalInit?.(ctx);
    } else {
      throw ctx.initErr("InvalidPluginType");
    }
  }
  plugins.sort((a, b) => {
    const lenDiff = b.name.length - a.name.length;
    if (lenDiff !== 0) return lenDiff;
    return a.name.localeCompare(b.name);
  });
  pluginRegexs = plugins.map(
    (plugin) => RegExp(`^${plugin.name}([A-Z]|_|$)`)
  );
}
function applyEls(els) {
  const ignore = `[${aliasify("ignore")}]`;
  for (const el of els) {
    if (!el.closest(ignore)) {
      for (const key in el.dataset) {
        applyAttributePlugin(el, key, el.dataset[key]);
      }
    }
  }
}
function cleanupEls(els) {
  for (const el of els) {
    const cleanups = removals.get(el);
    if (removals.delete(el)) {
      for (const cleanup2 of cleanups.values()) {
        cleanup2();
      }
      cleanups.clear();
    }
  }
}
function apply(root2 = document.body) {
  queueMicrotask(() => {
    applyEls([root2]);
    applyEls(root2.querySelectorAll("*"));
    if (!mutationObserver) {
      mutationObserver = new MutationObserver(observe);
      mutationObserver.observe(root2, {
        subtree: true,
        childList: true,
        attributes: true
      });
    }
  });
}
function applyAttributePlugin(el, attrKey, value) {
  if (attrKey.startsWith(alias)) {
    const rawKey = camel(alias ? attrKey.slice(alias.length) : attrKey);
    const plugin = plugins.find((_, i) => pluginRegexs[i].test(rawKey));
    if (plugin) {
      let [key, ...rawModifiers] = rawKey.slice(plugin.name.length).split(/__+/);
      const hasKey = !!key;
      if (hasKey) {
        key = camel(key);
      }
      const hasValue = !!value;
      const ctx = {
        plugin,
        actions,
        root,
        filtered,
        signal,
        computed,
        effect,
        mergePatch,
        peek,
        getPath,
        startBatch,
        endBatch,
        initErr: 0,
        el,
        rawKey,
        key,
        value,
        mods: /* @__PURE__ */ new Map(),
        runtimeErr: 0,
        rx: 0
      };
      ctx.initErr = initErr.bind(0, ctx);
      ctx.runtimeErr = runtimeErr.bind(0, ctx);
      if (plugin.shouldEvaluate === void 0 || plugin.shouldEvaluate === true) {
        ctx.rx = generateReactiveExpression(ctx);
      }
      const keyReq = plugin.keyReq || "allowed";
      if (hasKey) {
        if (keyReq === "denied") {
          throw ctx.runtimeErr(`${plugin.name}KeyNotAllowed`);
        }
      } else if (keyReq === "must") {
        throw ctx.runtimeErr(`${plugin.name}KeyRequired`);
      }
      const valReq = plugin.valReq || "allowed";
      if (hasValue) {
        if (valReq === "denied") {
          throw ctx.runtimeErr(`${plugin.name}ValueNotAllowed`);
        }
      } else if (valReq === "must") {
        throw ctx.runtimeErr(`${plugin.name}ValueRequired`);
      }
      if (keyReq === "exclusive" || valReq === "exclusive") {
        if (hasKey && hasValue) {
          throw ctx.runtimeErr(`${plugin.name}KeyAndValueProvided`);
        }
        if (!hasKey && !hasValue) {
          throw ctx.runtimeErr(`${plugin.name}KeyOrValueRequired`);
        }
      }
      for (const rawMod of rawModifiers) {
        const [label, ...mod] = rawMod.split(".");
        ctx.mods.set(
          camel(label),
          new Set(mod.map((t) => t.toLowerCase()))
        );
      }
      const cleanup2 = plugin.onLoad(ctx);
      if (cleanup2) {
        let cleanups = removals.get(el);
        if (cleanups) {
          cleanups.get(rawKey)?.();
        } else {
          cleanups = /* @__PURE__ */ new Map();
          removals.set(el, cleanups);
        }
        cleanups.set(rawKey, cleanup2);
      }
    }
  }
}
function observe(mutations) {
  const ignore = `[${aliasify("ignore")}]`;
  for (const {
    target,
    type,
    attributeName,
    addedNodes,
    removedNodes
  } of mutations) {
    if (type === "childList") {
      for (const node of removedNodes) {
        if (isHTMLOrSVG(node)) {
          cleanupEls([node]);
          cleanupEls(node.querySelectorAll("*"));
        }
      }
      for (const node of addedNodes) {
        if (isHTMLOrSVG(node)) {
          applyEls([node]);
          applyEls(node.querySelectorAll("*"));
        }
      }
    } else if (type === "attributes") {
      if (isHTMLOrSVG(target) && !target.closest(ignore)) {
        const key = camel(attributeName.slice(5));
        const value = target.getAttribute(attributeName);
        if (value === null) {
          const cleanups = removals.get(target);
          if (cleanups) {
            cleanups.get(key)?.();
            cleanups.delete(key);
          }
        } else {
          applyAttributePlugin(target, key, value);
        }
      }
    }
  }
}
function generateReactiveExpression(ctx) {
  let expr = "";
  const attrPlugin = ctx.plugin || void 0;
  if (attrPlugin?.returnsValue) {
    const statementRe = /(\/(\\\/|[^/])*\/|"(\\"|[^"])*"|'(\\'|[^'])*'|`(\\`|[^`])*`|\(\s*((function)\s*\(\s*\)|(\(\s*\))\s*=>)\s*(?:\{[\s\S]*?\}|[^;){]*)\s*\)\s*\(\s*\)|[^;])+/gm;
    const statements = ctx.value.trim().match(statementRe);
    if (statements) {
      const lastIdx = statements.length - 1;
      const last = statements[lastIdx].trim();
      if (!last.startsWith("return")) {
        statements[lastIdx] = `return (${last});`;
      }
      expr = statements.join(";\n");
    }
  } else {
    expr = ctx.value.trim();
  }
  expr = expr.replace(/\$\['([a-zA-Z_$\d][\w$]*)'\]/g, "$$$1").replace(/\$([a-zA-Z_\d]\w*(?:[.-]\w+)*)/g, (_, signalName) => {
    const parts = signalName.split(".");
    return parts.reduce(
      (acc, part) => `${acc}['${part}']`,
      "$"
    );
  }).replace(
    /\[(\$[a-zA-Z_\d]\w*)\]/g,
    (_, varName) => `[$['${varName.slice(1)}']]`
  );
  const escaped = /* @__PURE__ */ new Map();
  const escapeRe = RegExp(`(?:${DSP})(.*?)(?:${DSS})`, "gm");
  let counter = 0;
  for (const match of expr.matchAll(escapeRe)) {
    const k = match[1];
    const v = `dsEscaped${counter++}`;
    escaped.set(v, k);
    expr = expr.replace(DSP + k + DSS, v);
  }
  const nameGen = (prefix, name) => {
    return `${prefix}${snake(name).replaceAll(/\./g, "_")}`;
  };
  const actionsCalled = /* @__PURE__ */ new Set();
  const actionsRe = RegExp(`@(${Object.keys(actions).join("|")})\\(`, "gm");
  const actionMatches = [...expr.matchAll(actionsRe)];
  const actionNames = /* @__PURE__ */ new Set();
  const actionFns = /* @__PURE__ */ new Set();
  if (actionMatches.length) {
    const actionPrefix = `${DATASTAR}Act_`;
    for (const match of actionMatches) {
      const actionName = match[1];
      const action = actions[actionName];
      if (!action) {
        continue;
      }
      actionsCalled.add(actionName);
      const name = nameGen(actionPrefix, actionName);
      expr = expr.replace(`@${actionName}(`, `${name}(`);
      actionNames.add(name);
      actionFns.add((...args) => action.fn(ctx, ...args));
    }
  }
  for (const [k, v] of escaped) {
    expr = expr.replace(k, v);
  }
  ctx.fnContent = expr;
  try {
    const fn = Function(
      "el",
      "$",
      ...attrPlugin?.argNames || [],
      ...actionNames,
      expr
    );
    return (...args) => {
      try {
        return fn(ctx.el, root, ...args, ...actionFns);
      } catch (e) {
        throw ctx.runtimeErr("ExecuteExpression", {
          error: e.message
        });
      }
    };
  } catch (error) {
    throw ctx.runtimeErr("GenerateExpression", {
      error: error.message
    });
  }
}
const Peek = {
  type: "action",
  name: "peek",
  fn: ({ peek: peek2 }, fn) => {
    return peek2(fn);
  }
};
const SetAll = {
  type: "action",
  name: "setAll",
  fn: ({ filtered: filtered2, mergePatch: mergePatch2, peek: peek2 }, value, filter) => {
    peek2(() => {
      const masked = filtered2(filter);
      updateLeaves(masked, () => value);
      mergePatch2(masked);
    });
  }
};
const ToggleAll = {
  type: "action",
  name: "toggleAll",
  fn: ({ filtered: filtered2, mergePatch: mergePatch2, peek: peek2 }, filter) => {
    peek2(() => {
      const masked = filtered2(filter);
      updateLeaves(masked, (oldValue) => !oldValue);
      mergePatch2(masked);
    });
  }
};
const Dispatch = {
  type: "action",
  name: "dispatch",
  fn: ({ el }, eventName, data, options) => {
    if (!eventName || typeof eventName !== "string") {
      console.error(
        "[Hyper Dispatch] Invalid event name. Must be a non-empty string.",
        { eventName }
      );
      return;
    }
    const opts = {
      selector: options?.selector,
      window: options?.window ?? !options?.selector,
      // Default to window if no selector
      bubbles: options?.bubbles ?? true,
      cancelable: options?.cancelable ?? true,
      composed: options?.composed ?? true
    };
    const event = new CustomEvent(eventName, {
      detail: data || {},
      bubbles: opts.bubbles,
      cancelable: opts.cancelable,
      composed: opts.composed
    });
    if (opts.selector) {
      const targets = document.querySelectorAll(opts.selector);
      if (targets.length === 0) {
        console.warn(
          `[Hyper Dispatch] No elements found for selector: ${opts.selector}`,
          { eventName, selector: opts.selector }
        );
        return;
      }
      targets.forEach((target) => {
        target.dispatchEvent(new CustomEvent(eventName, {
          detail: data || {},
          bubbles: opts.bubbles,
          cancelable: opts.cancelable,
          composed: opts.composed
        }));
      });
      return;
    }
    if (opts.window) {
      window.dispatchEvent(event);
      return;
    }
    el.dispatchEvent(event);
  }
};
const FileUrl = {
  type: "action",
  name: "fileUrl",
  fn: (_ctx, fileSource, options) => {
    const opts = options || {};
    const fallback = opts.fallback || "";
    const defaultMime = opts.defaultMime || "application/octet-stream";
    const mimeSignal = opts.mimeSignal;
    if (fileSource == null) {
      return fallback;
    }
    if (Array.isArray(fileSource)) {
      if (fileSource.length === 0) {
        return fallback;
      }
      const base64Content = fileSource[0];
      if (!base64Content || typeof base64Content !== "string") {
        return fallback;
      }
      let mimeType = defaultMime;
      if (mimeSignal && typeof window !== "undefined") {
        const mimeArray = (window.$ || {})[mimeSignal];
        if (Array.isArray(mimeArray) && mimeArray.length > 0) {
          mimeType = mimeArray[0] || defaultMime;
        }
      }
      return `data:${mimeType};base64,${base64Content}`;
    }
    if (typeof fileSource === "string") {
      const trimmed = fileSource.trim();
      if (!trimmed) {
        return fallback;
      }
      return trimmed;
    }
    if (typeof fileSource === "object") {
      console.warn(
        "FileUrl action received unexpected object:",
        fileSource
      );
    }
    return fallback;
  }
};
const Attr = {
  type: "attribute",
  name: "attr",
  valReq: "must",
  returnsValue: true,
  onLoad: ({ el, effect: effect2, key, rx }) => {
    const syncAttr = (key2, val) => {
      if (val === "" || val === true) {
        el.setAttribute(key2, "");
      } else if (val === false || val == null) {
        el.removeAttribute(key2);
      } else if (typeof val === "string") {
        el.setAttribute(key2, val);
      } else {
        el.setAttribute(key2, JSON.stringify(val));
      }
    };
    key = kebab(key);
    const update2 = key ? () => {
      observer.disconnect();
      const val = rx();
      syncAttr(key, val);
      observer.observe(el, {
        attributeFilter: [key]
      });
    } : () => {
      observer.disconnect();
      const obj = rx();
      const attributeFilter = Object.keys(obj);
      for (const key2 of attributeFilter) {
        syncAttr(key2, obj[key2]);
      }
      observer.observe(el, {
        attributeFilter
      });
    };
    const observer = new MutationObserver(update2);
    const cleanup2 = effect2(update2);
    return () => {
      observer.disconnect();
      cleanup2();
    };
  }
};
const dataURIRegex = /^data:(?<mime>[^;]+);base64,(?<contents>.*)$/;
const empty = Symbol("empty");
const Bind = {
  type: "attribute",
  name: "bind",
  keyReq: "exclusive",
  valReq: "exclusive",
  shouldEvaluate: false,
  onLoad: ({
    el,
    key,
    mods,
    value,
    effect: effect2,
    mergePatch: mergePatch2,
    runtimeErr: runtimeErr2,
    getPath: getPath2
  }) => {
    const signalName = key ? modifyCasing(key, mods) : value;
    let get = (el2, type2) => type2 === "number" ? +el2.value : el2.value;
    let set = (value2) => {
      el.value = `${value2}`;
    };
    if (el instanceof HTMLInputElement) {
      switch (el.type) {
        case "range":
        case "number":
          get = (el2, type2) => type2 === "string" ? el2.value : +el2.value;
          break;
        case "checkbox":
          get = (el2, type2) => {
            if (el2.value !== "on") {
              if (type2 === "boolean") {
                return el2.checked;
              } else {
                return el2.checked ? el2.value : "";
              }
            } else {
              if (type2 === "string") {
                return el2.checked ? el2.value : "";
              } else {
                return el2.checked;
              }
            }
          };
          set = (value2) => {
            el.checked = typeof value2 === "string" ? value2 === el.value : value2;
          };
          break;
        case "radio":
          if (!el.getAttribute("name")?.length) {
            el.setAttribute("name", signalName);
          }
          get = (el2, type2) => el2.checked ? type2 === "number" ? +el2.value : el2.value : empty;
          set = (value2) => {
            el.checked = value2 === (typeof value2 === "number" ? +el.value : el.value);
          };
          break;
        case "file": {
          const syncSignal2 = () => {
            const files = [...el.files || []];
            const contents = [];
            const mimes = [];
            const names = [];
            Promise.all(
              files.map(
                (f) => new Promise((resolve) => {
                  const reader = new FileReader();
                  reader.onload = () => {
                    if (typeof reader.result !== "string") {
                      throw runtimeErr2("InvalidFileResultType", {
                        resultType: typeof reader.result
                      });
                    }
                    const match = reader.result.match(dataURIRegex);
                    if (!match?.groups) {
                      throw runtimeErr2("InvalidDataUri", {
                        result: reader.result
                      });
                    }
                    contents.push(match.groups.contents);
                    mimes.push(match.groups.mime);
                    names.push(f.name);
                  };
                  reader.onloadend = () => resolve();
                  reader.readAsDataURL(f);
                })
              )
            ).then(() => {
              mergePatch2(
                pathToObj(
                  {},
                  {
                    [signalName]: contents,
                    [`${signalName}Mimes`]: mimes,
                    [`${signalName}Names`]: names
                  }
                )
              );
            });
          };
          el.addEventListener("change", syncSignal2);
          el.addEventListener("input", syncSignal2);
          return () => {
            el.removeEventListener("change", syncSignal2);
            el.removeEventListener("input", syncSignal2);
          };
        }
      }
    } else if (el instanceof HTMLSelectElement) {
      if (el.multiple) {
        const typeMap = /* @__PURE__ */ new Map();
        get = (el2) => [...el2.selectedOptions].map((option) => {
          const type2 = typeMap.get(option.value);
          return type2 === "string" || type2 == null ? option.value : +option.value;
        });
        set = (value2) => {
          for (const option of el.options) {
            if (value2.includes(option.value)) {
              typeMap.set(option.value, "string");
              option.selected = true;
            } else if (value2.includes(+option.value)) {
              typeMap.set(option.value, "number");
              option.selected = true;
            } else {
              option.selected = false;
            }
          }
        };
      }
    } else if (el instanceof HTMLTextAreaElement) ;
    else {
      get = (el2) => "value" in el2 ? el2.value : el2.getAttribute("value");
      set = (value2) => {
        if ("value" in el) {
          el.value = value2;
        } else {
          el.setAttribute("value", value2);
        }
      };
    }
    const initialValue = getPath2(signalName);
    const type = typeof initialValue;
    let path = signalName;
    if (Array.isArray(initialValue) && !(el instanceof HTMLSelectElement && el.multiple)) {
      const inputs = document.querySelectorAll(
        `[${aliasify("bind")}-${key}],[${aliasify("bind")}="${value}"]`
      );
      const pathObj = {};
      let i = 0;
      for (const input of inputs) {
        pathObj[`${path}.${i}`] = get(input, "none");
        if (el === input) {
          break;
        }
        i++;
      }
      mergePatch2(pathToObj({}, pathObj), { ifMissing: true });
      path = `${path}.${i}`;
    } else {
      mergePatch2(pathToObj({}, { [path]: get(el, type) }), {
        ifMissing: true
      });
    }
    const syncSignal = () => {
      const signalValue = getPath2(path);
      if (signalValue != null) {
        const value2 = get(el, typeof signalValue);
        if (value2 !== empty) {
          mergePatch2(pathToObj({}, { [path]: value2 }));
        }
      }
    };
    el.addEventListener("input", syncSignal);
    el.addEventListener("change", syncSignal);
    const cleanup2 = effect2(() => {
      set(getPath2(path));
    });
    return () => {
      cleanup2();
      el.removeEventListener("input", syncSignal);
      el.removeEventListener("change", syncSignal);
    };
  }
};
const Class = {
  type: "attribute",
  name: "class",
  valReq: "must",
  returnsValue: true,
  onLoad: ({ key, el, effect: effect2, mods, rx }) => {
    if (key) {
      key = modifyCasing(kebab(key), mods);
    }
    const callback = () => {
      observer.disconnect();
      const classes = key ? { [key]: rx() } : rx();
      for (const k in classes) {
        const classNames = k.split(/\s+/).filter((cn) => cn.length > 0);
        if (classes[k]) {
          for (const name of classNames) {
            if (!el.classList.contains(name)) {
              el.classList.add(name);
            }
          }
        } else {
          for (const name of classNames) {
            if (el.classList.contains(name)) {
              el.classList.remove(name);
            }
          }
        }
      }
      observer.observe(el, { attributeFilter: ["class"] });
    };
    const observer = new MutationObserver(callback);
    const cleanup2 = effect2(callback);
    return () => {
      observer.disconnect();
      cleanup2();
      const classes = key ? { [key]: rx() } : rx();
      for (const k in classes) {
        const classNames = k.split(/\s+/).filter((cn) => cn.length > 0);
        for (const name of classNames) {
          el.classList.remove(name);
        }
      }
    };
  }
};
const Computed = {
  type: "attribute",
  name: "computed",
  keyReq: "must",
  valReq: "must",
  returnsValue: true,
  onLoad: ({ key, mods, rx, computed: computed2, mergePatch: mergePatch2 }) => {
    mergePatch2(pathToObj({}, { [modifyCasing(key, mods)]: computed2(rx) }));
  }
};
const Effect = {
  type: "attribute",
  name: "effect",
  keyReq: "denied",
  valReq: "must",
  onLoad: ({ effect: effect2, rx }) => effect2(rx)
};
const DATASTAR_FETCH_EVENT = `${DATASTAR}-fetch`;
const STARTED = "started";
const FINISHED = "finished";
const ERROR = "error";
const RETRYING = "retrying";
const RETRIES_FAILED = "retries-failed";
function datastarSSEEventWatcher(eventType, fn) {
  document.addEventListener(
    DATASTAR_FETCH_EVENT,
    (event) => {
      if (event.detail.type === eventType) {
        const { argsRaw } = event.detail;
        fn(argsRaw);
      }
    }
  );
}
const Indicator = {
  type: "attribute",
  name: "indicator",
  keyReq: "exclusive",
  valReq: "exclusive",
  shouldEvaluate: false,
  onLoad: ({ el, key, mods, mergePatch: mergePatch2, value }) => {
    const signalName = key ? modifyCasing(key, mods) : value;
    mergePatch2(pathToObj({}, { [signalName]: false }), { ifMissing: true });
    const watcher = (event) => {
      const { type, el: elt } = event.detail;
      if (elt !== el) {
        return;
      }
      switch (type) {
        case STARTED:
          mergePatch2(pathToObj({}, { [signalName]: true }));
          break;
        case FINISHED:
          mergePatch2(pathToObj({}, { [signalName]: false }));
          break;
      }
    };
    document.addEventListener(DATASTAR_FETCH_EVENT, watcher);
    return () => {
      mergePatch2(pathToObj({}, { [signalName]: false }));
      document.removeEventListener(DATASTAR_FETCH_EVENT, watcher);
    };
  }
};
const JsonSignals = {
  type: "attribute",
  name: "jsonSignals",
  keyReq: "denied",
  onLoad: ({ el, effect: effect2, value, filtered: filtered2, mods }) => {
    const spaces = mods.has("terse") ? 0 : 2;
    let filters = {};
    if (value) {
      filters = jsStrToObject(value);
    }
    const callback = () => {
      observer.disconnect();
      el.textContent = JSON.stringify(filtered2(filters), null, spaces);
      observer.observe(el, {
        childList: true,
        characterData: true,
        subtree: true
      });
    };
    const observer = new MutationObserver(callback);
    const cleanup2 = effect2(callback);
    return () => {
      observer.disconnect();
      cleanup2();
    };
  }
};
function tagToMs(args) {
  if (!args || args.size <= 0) return 0;
  for (const arg of args) {
    if (arg.endsWith("ms")) {
      return +arg.replace("ms", "");
    }
    if (arg.endsWith("s")) {
      return +arg.replace("s", "") * 1e3;
    }
    try {
      return Number.parseFloat(arg);
    } catch (_) {
    }
  }
  return 0;
}
function tagHas(tags, tag, defaultValue = false) {
  if (!tags) return defaultValue;
  return tags.has(tag.toLowerCase());
}
function delay(callback, wait) {
  return (...args) => {
    setTimeout(() => {
      callback(...args);
    }, wait);
  };
}
function debounce(callback, wait, leading = false, trailing = true) {
  let timer = 0;
  return (...args) => {
    timer && clearTimeout(timer);
    if (leading && !timer) {
      callback(...args);
    }
    timer = setTimeout(() => {
      if (trailing) {
        callback(...args);
      }
      timer && clearTimeout(timer);
    }, wait);
  };
}
function throttle(callback, wait, leading = true, trailing = false) {
  let waiting = false;
  return (...args) => {
    if (waiting) return;
    if (leading) {
      callback(...args);
    }
    waiting = true;
    setTimeout(() => {
      waiting = false;
      if (trailing) {
        callback(...args);
      }
    }, wait);
  };
}
function modifyTiming(callback, mods) {
  const delayArgs = mods.get("delay");
  if (delayArgs) {
    const wait = tagToMs(delayArgs);
    callback = delay(callback, wait);
  }
  const debounceArgs = mods.get("debounce");
  if (debounceArgs) {
    const wait = tagToMs(debounceArgs);
    const leading = tagHas(debounceArgs, "leading", false);
    const trailing = !tagHas(debounceArgs, "notrail", false);
    callback = debounce(callback, wait, leading, trailing);
  }
  const throttleArgs = mods.get("throttle");
  if (throttleArgs) {
    const wait = tagToMs(throttleArgs);
    const leading = !tagHas(throttleArgs, "noleading", false);
    const trailing = tagHas(throttleArgs, "trail", false);
    callback = throttle(callback, wait, leading, trailing);
  }
  return callback;
}
const supportsViewTransitions = !!document.startViewTransition;
function modifyViewTransition(callback, mods) {
  if (mods.has("viewtransition") && supportsViewTransitions) {
    const cb = callback;
    callback = (...args) => document.startViewTransition(() => cb(...args));
  }
  return callback;
}
const On = {
  type: "attribute",
  name: "on",
  keyReq: "must",
  valReq: "must",
  argNames: ["evt"],
  onLoad: (ctx) => {
    const { el, key, mods, rx, startBatch: startBatch2, endBatch: endBatch2 } = ctx;
    let target = el;
    if (mods.has("window")) target = window;
    let callback = (evt) => {
      if (evt) {
        if (mods.has("prevent")) {
          evt.preventDefault();
        }
        if (mods.has("stop")) {
          evt.stopPropagation();
        }
        if (!(evt.isTrusted || evt instanceof CustomEvent || mods.has("trusted"))) {
          return;
        }
        ctx.evt = evt;
      }
      startBatch2();
      rx(evt);
      endBatch2();
    };
    callback = modifyTiming(callback, mods);
    callback = modifyViewTransition(callback, mods);
    const evtListOpts = {
      capture: mods.has("capture"),
      passive: mods.has("passive"),
      once: mods.has("once")
    };
    if (mods.has("outside")) {
      target = document;
      const cb = callback;
      callback = (evt) => {
        if (!el.contains(evt?.target)) {
          cb(evt);
        }
      };
    }
    let eventName = kebab(key);
    eventName = modifyCasing(eventName, mods);
    if (eventName === DATASTAR_FETCH_EVENT || eventName === DATASTAR_SIGNAL_PATCH_EVENT) {
      target = document;
    }
    if (el instanceof HTMLFormElement && eventName === "submit") {
      const cb = callback;
      callback = (evt) => {
        evt?.preventDefault();
        cb(evt);
      };
    }
    target.addEventListener(eventName, callback, evtListOpts);
    return () => {
      target.removeEventListener(eventName, callback);
    };
  }
};
const once = /* @__PURE__ */ new WeakSet();
const OnIntersect = {
  type: "attribute",
  name: "onIntersect",
  keyReq: "denied",
  onLoad: ({ el, mods, rx, startBatch: startBatch2, endBatch: endBatch2 }) => {
    let callback = () => {
      startBatch2();
      rx();
      endBatch2();
    };
    callback = modifyTiming(callback, mods);
    callback = modifyViewTransition(callback, mods);
    const options = { threshold: 0 };
    if (mods.has("full")) {
      options.threshold = 1;
    } else if (mods.has("half")) {
      options.threshold = 0.5;
    }
    let observer = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          if (entry.isIntersecting) {
            callback();
            if (observer && once.has(el)) {
              observer.disconnect();
            }
          }
        }
      },
      options
    );
    observer.observe(el);
    if (mods.has("once")) {
      once.add(el);
    }
    return () => {
      if (!mods.has("once")) {
        once.delete(el);
      }
      if (observer) {
        observer.disconnect();
        observer = null;
      }
    };
  }
};
const OnInterval = {
  type: "attribute",
  name: "onInterval",
  keyReq: "denied",
  valReq: "must",
  onLoad: ({ mods, rx, startBatch: startBatch2, endBatch: endBatch2 }) => {
    let callback = () => {
      startBatch2();
      rx();
      endBatch2();
    };
    callback = modifyViewTransition(callback, mods);
    let duration = 1e3;
    const durationArgs = mods.get("duration");
    if (durationArgs) {
      duration = tagToMs(durationArgs);
      const leading = tagHas(durationArgs, "leading", false);
      if (leading) {
        callback();
      }
    }
    const intervalId = setInterval(callback, duration);
    return () => {
      clearInterval(intervalId);
    };
  }
};
const OnLoad = {
  type: "attribute",
  name: "onLoad",
  keyReq: "denied",
  valReq: "must",
  onLoad: ({ rx, mods, startBatch: startBatch2, endBatch: endBatch2 }) => {
    let callback = () => {
      startBatch2();
      rx();
      endBatch2();
    };
    callback = modifyViewTransition(callback, mods);
    let wait = 0;
    const delayArgs = mods.get("delay");
    if (delayArgs) {
      wait = tagToMs(delayArgs);
    }
    callback = delay(callback, wait);
    callback();
  }
};
const OnSignalPatch = {
  type: "attribute",
  name: "onSignalPatch",
  valReq: "must",
  argNames: ["patch"],
  returnsValue: true,
  onLoad: ({
    el,
    key,
    mods,
    plugin,
    rx,
    filtered: filtered2,
    runtimeErr: runtimeErr2,
    startBatch: startBatch2,
    endBatch: endBatch2
  }) => {
    if (!!key && key !== "filter") {
      throw runtimeErr2(`${plugin.name}KeyNotAllowed`);
    }
    const filtersRaw = el.getAttribute("data-on-signal-patch-filter");
    let filters = {};
    if (filtersRaw) {
      filters = jsStrToObject(filtersRaw);
    }
    const callback = modifyTiming(
      (evt) => {
        const watched = filtered2(filters, evt.detail);
        if (!isEmpty(watched)) {
          startBatch2();
          rx(watched);
          endBatch2();
        }
      },
      mods
    );
    document.addEventListener(DATASTAR_SIGNAL_PATCH_EVENT, callback);
    return () => {
      document.removeEventListener(DATASTAR_SIGNAL_PATCH_EVENT, callback);
    };
  }
};
const Ref = {
  type: "attribute",
  name: "ref",
  keyReq: "exclusive",
  valReq: "exclusive",
  shouldEvaluate: false,
  onLoad: ({ el, key, mods, value, mergePatch: mergePatch2 }) => {
    const signalName = key ? modifyCasing(key, mods) : value;
    mergePatch2(pathToObj({}, { [signalName]: el }));
  }
};
const NONE = "none";
const DISPLAY = "display";
const Show = {
  type: "attribute",
  name: "show",
  keyReq: "denied",
  valReq: "must",
  returnsValue: true,
  onLoad: ({ el, effect: effect2, rx }) => {
    const update2 = () => {
      observer.disconnect();
      const shouldShow = rx();
      if (shouldShow) {
        if (el.style.display === NONE) el.style.removeProperty(DISPLAY);
      } else {
        el.style.setProperty(DISPLAY, NONE);
      }
      observer.observe(el, { attributeFilter: ["style"] });
    };
    const observer = new MutationObserver(update2);
    const cleanup2 = effect2(update2);
    return () => {
      observer.disconnect();
      cleanup2();
    };
  }
};
const Signals = {
  type: "attribute",
  name: "signals",
  returnsValue: true,
  onLoad: ({ key, mods, rx, mergePatch: mergePatch2 }) => {
    const ifMissing = mods.has("ifmissing");
    if (key) {
      key = modifyCasing(key, mods);
      mergePatch2(pathToObj({}, { [key]: rx() }), { ifMissing });
    } else {
      const patch = rx();
      const pathObj = {};
      for (const key2 in patch) {
        pathObj[key2] = patch[key2];
      }
      mergePatch2(pathToObj({}, pathObj), { ifMissing });
    }
  }
};
const Style = {
  type: "attribute",
  name: "style",
  valReq: "must",
  returnsValue: true,
  onLoad: ({ key, el, effect: effect2, rx }) => {
    const { style } = el;
    const initialStyles = /* @__PURE__ */ new Map();
    key &&= kebab(key);
    const apply2 = (prop, value) => {
      const initial = initialStyles.get(prop);
      if (!value && value !== 0) {
        initial !== void 0 && (initial ? style.setProperty(prop, initial) : style.removeProperty(prop));
      } else {
        initial === void 0 && initialStyles.set(prop, style.getPropertyValue(prop));
        style.setProperty(prop, String(value));
      }
    };
    const update2 = () => {
      observer.disconnect();
      if (key) {
        apply2(key, rx());
      } else {
        const styles = rx();
        for (const [prop, initial] of initialStyles) {
          prop in styles || (initial ? style.setProperty(prop, initial) : style.removeProperty(prop));
        }
        for (const prop in styles) {
          apply2(kebab(prop), styles[prop]);
        }
      }
      observer.observe(el, { attributeFilter: ["style"] });
    };
    const observer = new MutationObserver(update2);
    const cleanup2 = effect2(update2);
    return () => {
      observer.disconnect();
      cleanup2();
      for (const [prop, initial] of initialStyles) {
        initial ? style.setProperty(prop, initial) : style.removeProperty(prop);
      }
    };
  }
};
const Text = {
  type: "attribute",
  name: "text",
  keyReq: "denied",
  valReq: "must",
  returnsValue: true,
  onLoad: ({ el, effect: effect2, rx }) => {
    const update2 = () => {
      observer.disconnect();
      el.textContent = `${rx()}`;
      observer.observe(el, {
        childList: true,
        characterData: true,
        subtree: true
      });
    };
    const observer = new MutationObserver(update2);
    const cleanup2 = effect2(update2);
    return () => {
      observer.disconnect();
      cleanup2();
    };
  }
};
const Error$1 = {
  type: "attribute",
  name: "error",
  keyReq: "denied",
  valReq: "must",
  shouldEvaluate: false,
  onLoad: ({ el, value, effect: effect2, computed: computed2, getPath: getPath2, mergePatch: mergePatch2 }) => {
    const fieldName = value.trim();
    mergePatch2(pathToObj({}, { errors: {} }), { ifMissing: true });
    const errorComputed = computed2(() => {
      const errors = getPath2("errors");
      if (errors && errors[fieldName]) {
        const fieldErrors = errors[fieldName];
        if (Array.isArray(fieldErrors) && fieldErrors.length > 0) {
          return fieldErrors[0];
        } else if (typeof fieldErrors === "string") {
          return fieldErrors;
        }
      }
      return null;
    });
    const cleanup2 = effect2(() => {
      const errorMessage = errorComputed();
      if (errorMessage) {
        el.style.removeProperty("display");
        el.textContent = errorMessage;
      } else {
        el.style.setProperty("display", "none");
        el.textContent = "";
      }
    });
    return cleanup2;
  }
};
const iterationStates = /* @__PURE__ */ new WeakMap();
const For = {
  type: "attribute",
  name: "for",
  keyReq: "denied",
  valReq: "must",
  shouldEvaluate: false,
  onLoad: (ctx) => {
    const { el, value, mods, effect: effect2, getPath: getPath2, runtimeErr: runtimeErr2, startBatch: startBatch2, endBatch: endBatch2, peek: peek2 } = ctx;
    if (!(el instanceof HTMLTemplateElement)) {
      throw runtimeErr2("ForMustBeOnTemplate", {
        message: "data-for must be used on <template> elements"
      });
    }
    const parsed = parseExpression(value);
    if (!parsed) {
      throw runtimeErr2("InvalidForExpression", {
        expression: value,
        expected: 'Format: "item in $items", "[name, age] in $users", "item, index in $items"'
      });
    }
    const keyMod = mods.get("key");
    const keyExpression = keyMod && keyMod.size > 0 ? Array.from(keyMod)[0] : null;
    const template = el;
    template.style.display = "none";
    const templateContent = template.content.cloneNode(true);
    const sourceSignalPath = parsed.items;
    const sourceData = getPath2(sourceSignalPath);
    const isNormalized = shouldNormalizeData(sourceData);
    const iterationId = `__for_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    const state = {
      prevKeys: [],
      lookup: /* @__PURE__ */ new Map(),
      template,
      templateContent,
      iteratorNames: parsed,
      keyExpression,
      effectCleanup: null,
      sourceSignalPath,
      isNormalized,
      iterationId
    };
    iterationStates.set(el, state);
    const effectCleanup = effect2(() => {
      const sourceData2 = getPath2(sourceSignalPath);
      if (Array.isArray(sourceData2)) {
        const reactiveArray = ctx.root[sourceSignalPath];
        if (reactiveArray && reactiveArray.length > 0) {
          const firstItem = reactiveArray[0];
          if (firstItem && typeof firstItem === "object" && !Array.isArray(firstItem)) {
            for (const key in reactiveArray) {
              const item = reactiveArray[key];
              if (item && typeof item === "object") {
                for (const prop in item) {
                  void item[prop];
                }
              }
            }
          }
        }
      }
      const items = normalizeData(sourceData2);
      const newArrayWithKeys = items.map((item, index) => ({
        item,
        index,
        key: evaluateKey(keyExpression, item, index)
      }));
      peek2(() => {
        startBatch2();
        try {
          diffAndUpdate(newArrayWithKeys, state, ctx);
        } finally {
          endBatch2();
        }
      });
    });
    state.effectCleanup = effectCleanup;
    return () => {
      cleanup(state);
      iterationStates.delete(el);
    };
  }
};
function parseExpression(expression) {
  const forIteratorRE = /,([^,\}\]]*)(?:,([^,\}\]]*))?$/;
  const stripParensRE = /^\s*\(|\)\s*$/g;
  const forAliasRE = /([\s\S]*?)\s+(?:in|of)\s+([\s\S]*)/;
  const match = expression.match(forAliasRE);
  if (!match) return null;
  let itemsExpr = match[2].trim();
  if (itemsExpr.startsWith("$")) {
    itemsExpr = itemsExpr.slice(1);
  }
  const result = {
    item: "",
    items: itemsExpr
  };
  let item = match[1].replace(stripParensRE, "").trim();
  const iteratorMatch = item.match(forIteratorRE);
  if (iteratorMatch) {
    result.item = item.replace(forIteratorRE, "").trim();
    result.index = iteratorMatch[1].trim();
    if (iteratorMatch[2]) {
      result.collection = iteratorMatch[2].trim();
    }
  } else {
    result.item = item;
  }
  return result;
}
function diffAndUpdate(newArrayWithKeys, state, ctx) {
  const seen = /* @__PURE__ */ new Set();
  const finalArray = newArrayWithKeys.map((data, i) => {
    let finalKey = data.key;
    if (seen.has(finalKey)) {
      finalKey = `${finalKey}_dup_${i}`;
    }
    seen.add(finalKey);
    return { ...data, key: finalKey };
  });
  const newKeys = finalArray.map((d) => d.key);
  const prevKeys = state.prevKeys;
  const changeType = detectChange(prevKeys, newKeys);
  if (changeType === "no-change") {
    if (!state.isNormalized && finalArray.length > 0) {
      const firstItem = finalArray[0].item;
      if (firstItem && typeof firstItem === "object" && !Array.isArray(firstItem)) {
        for (const itemData of finalArray) {
          const loopEl = state.lookup.get(itemData.key);
          if (loopEl) {
            const sanitizedKey = sanitizeKey(itemData.key);
            ctx.mergePatch({
              [state.iterationId]: {
                [sanitizedKey]: itemData.item
              }
            });
          }
        }
      }
    }
    return;
  } else if (changeType === "simple-add") {
    handleAdd(finalArray, state, ctx);
  } else if (changeType === "simple-remove") {
    handleRemove(prevKeys, newKeys, state, ctx, finalArray);
  } else {
    handleReorder(finalArray, state, ctx);
  }
  state.prevKeys = [...newKeys];
}
function detectChange(prevKeys, newKeys) {
  if (prevKeys.length === newKeys.length && prevKeys.every((k, i) => k === newKeys[i])) {
    return "no-change";
  }
  if (prevKeys.length < newKeys.length) {
    if (prevKeys.every((k, i) => k === newKeys[i])) {
      return "simple-add";
    }
  }
  if (prevKeys.length > newKeys.length) {
    const newSet = new Set(newKeys);
    if (!newKeys.some((k) => !new Set(prevKeys).has(k))) {
      let newIdx = 0;
      for (const pk of prevKeys) {
        if (newSet.has(pk)) {
          if (newKeys[newIdx] !== pk) return "complex";
          newIdx++;
        }
      }
      return "simple-remove";
    }
  }
  return "complex";
}
function handleAdd(data, state, ctx) {
  const startIdx = state.prevKeys.length;
  let prevEl = state.template;
  if (startIdx > 0) {
    const lastKey = state.prevKeys[startIdx - 1];
    const last = state.lookup.get(lastKey);
    if (last) prevEl = last.el;
  }
  for (let i = startIdx; i < data.length; i++) {
    const item = data[i];
    const loopEl = createElement(item, state, ctx);
    state.lookup.set(item.key, loopEl);
    prevEl.after(loopEl.el);
    prevEl = loopEl.el;
    queueMicrotask(() => apply(loopEl.el));
  }
}
function handleRemove(prevKeys, newKeys, state, ctx, data) {
  const newSet = new Set(newKeys);
  for (const key of prevKeys) {
    if (!newSet.has(key)) {
      const loopEl = state.lookup.get(key);
      if (loopEl) {
        loopEl.el.remove();
        state.lookup.delete(key);
      }
    }
  }
  for (const itemData of data) {
    const loopEl = state.lookup.get(itemData.key);
    if (loopEl && loopEl.index !== itemData.index) {
      loopEl.index = itemData.index;
      if (!state.isNormalized) {
        const sanitizedKey = sanitizeKey(itemData.key);
        const currentData = ctx.root[state.iterationId]?.[sanitizedKey];
        if (currentData !== void 0) {
          ctx.mergePatch({
            [state.iterationId]: {
              [`${sanitizedKey}__index`]: itemData.index
            }
          });
        }
      }
    }
  }
}
function handleReorder(data, state, ctx) {
  const newSet = new Set(data.map((d) => d.key));
  for (const pk of state.prevKeys) {
    if (!newSet.has(pk)) {
      const loopEl = state.lookup.get(pk);
      if (loopEl) {
        loopEl.el.remove();
        state.lookup.delete(pk);
      }
    }
  }
  let prevEl = state.template;
  for (const item of data) {
    const existing = state.lookup.get(item.key);
    if (existing) {
      if (existing.index !== item.index) {
        existing.index = item.index;
        if (!state.isNormalized) {
          const sanitizedKey = sanitizeKey(item.key);
          const currentData = ctx.root[state.iterationId]?.[sanitizedKey];
          if (currentData !== void 0) {
            ctx.mergePatch({
              [state.iterationId]: {
                [`${sanitizedKey}__index`]: item.index
              }
            });
          }
        }
      }
      if (existing.el.previousElementSibling !== prevEl) {
        prevEl.after(existing.el);
      }
      prevEl = existing.el;
    } else {
      const loopEl = createElement(item, state, ctx);
      state.lookup.set(item.key, loopEl);
      prevEl.after(loopEl.el);
      prevEl = loopEl.el;
      queueMicrotask(() => apply(loopEl.el));
    }
  }
}
function createElement(itemData, state, ctx) {
  const { templateContent, sourceSignalPath, iteratorNames, isNormalized, iterationId } = state;
  const clone = templateContent.cloneNode(true);
  const el = clone.firstElementChild;
  let signalPath = sourceSignalPath;
  let indexSignalPath = null;
  if (!isNormalized) {
    const sanitizedKey = sanitizeKey(itemData.key);
    signalPath = `${iterationId}.${sanitizedKey}`;
    indexSignalPath = `${iterationId}.${sanitizedKey}__index`;
    ctx.mergePatch({
      [iterationId]: {
        [sanitizedKey]: itemData.item,
        [`${sanitizedKey}__index`]: itemData.index
      }
    });
  }
  transformElement(el, iteratorNames, itemData.index, signalPath, indexSignalPath, isNormalized, itemData.item);
  return {
    el,
    key: itemData.key,
    index: itemData.index
  };
}
function sanitizeKey(key) {
  return String(key).replace(/[^a-zA-Z0-9_]/g, "_");
}
function transformElement(el, iterators, index, signalPath, indexSignalPath, isNormalized, value) {
  const process = (elem) => {
    Array.from(elem.attributes).forEach((attr) => {
      if (attr.name.startsWith("data-") && attr.value) {
        attr.value = transformExpression(
          attr.value,
          attr.name,
          iterators,
          index,
          signalPath,
          indexSignalPath,
          isNormalized,
          value
        );
      }
    });
    if (elem instanceof HTMLTemplateElement && elem.content) {
      Array.from(elem.content.children).forEach((child) => process(child));
    } else {
      Array.from(elem.children).forEach((child) => process(child));
    }
  };
  process(el);
}
function transformExpression(expr, attrName, iterators, index, signalPath, indexSignalPath, isNormalized, value) {
  let result = expr;
  const isSignalName = /^(bind|ref|indicator|signals|computed)/.test(attrName.replace(/^data-/, ""));
  const literals = [];
  result = result.replace(/'(?:[^'\\]|\\.)*'/g, (m) => (literals.push(m), `__LIT${literals.length - 1}__`)).replace(/"(?:[^"\\]|\\.)*"/g, (m) => (literals.push(m), `__LIT${literals.length - 1}__`)).replace(/`(?:[^`\\]|\\.)*`/g, (m) => (literals.push(m), `__LIT${literals.length - 1}__`));
  if (iterators.index) {
    if (indexSignalPath && !isNormalized) {
      const prefix = isSignalName ? "" : "$";
      result = result.replace(new RegExp(`\\b${esc(iterators.index)}\\b`, "g"), prefix + indexSignalPath);
    } else {
      result = result.replace(new RegExp(`\\b${esc(iterators.index)}\\b`, "g"), index.toString());
    }
  }
  if (iterators.collection) {
    const prefix = isSignalName ? "" : "$";
    result = result.replace(new RegExp(`\\b${esc(iterators.collection)}\\b`, "g"), prefix + signalPath);
  }
  if (isDestructured(iterators.item)) {
    const vars = extractVars(iterators.item);
    const isArrayDestructuring = iterators.item.trim().startsWith("[");
    vars.forEach((v, idx) => {
      let varSignalPath;
      let varValue;
      if (isArrayDestructuring) {
        varSignalPath = `${signalPath}.${idx}`;
        varValue = Array.isArray(value) ? value[idx] : value;
      } else {
        varSignalPath = `${signalPath}.${v}`;
        varValue = value && typeof value === "object" ? value[v] : value;
      }
      result = transformVar(result, v, varSignalPath, isSignalName, isNormalized, varValue);
    });
  } else {
    result = transformVar(result, iterators.item, signalPath, isSignalName, isNormalized, value);
  }
  result = result.replace(/__LIT(\d+)__/g, (_, i) => literals[parseInt(i)]);
  return result;
}
function transformVar(expr, varName, signalPath, isSignalName, isNormalized, value, _iterators) {
  if (isNormalized) {
    return expr.replace(new RegExp(`\\b${esc(varName)}\\b(?!\\.)`, "g"), JSON.stringify(value));
  }
  const prefix = isSignalName ? "" : "$";
  expr = expr.replace(
    new RegExp(`\\b${esc(varName)}\\.([a-zA-Z_][a-zA-Z0-9_.]*)\\b`, "g"),
    `${prefix}${signalPath}.$1`
  );
  expr = expr.replace(
    new RegExp(`\\b${esc(varName)}\\b(?!\\.)`, "g"),
    prefix + signalPath
  );
  return expr;
}
function shouldNormalizeData(data) {
  return typeof data === "number" || data && typeof data === "object" && !Array.isArray(data);
}
function normalizeData(data) {
  if (data == null) return [];
  if (Array.isArray(data)) return data;
  if (typeof data === "number") return Array.from({ length: data }, (_, i) => i + 1);
  if (typeof data === "object") return Object.entries(data);
  return [data];
}
function evaluateKey(keyExpr, item, index) {
  if (!keyExpr) {
    if (item && typeof item === "object") {
      if ("id" in item && item.id != null) return item.id;
      if ("uuid" in item && item.uuid != null) return item.uuid;
      if ("key" in item && item.key != null) return item.key;
    }
    return index;
  }
  if (keyExpr === "index") return index;
  if (keyExpr.includes(".")) {
    const parts = keyExpr.split(".");
    let val = item;
    for (const part of parts) {
      if (val && typeof val === "object" && part in val) {
        val = val[part];
      } else {
        return index;
      }
    }
    return val !== void 0 && val !== null ? val : index;
  }
  if (item && typeof item === "object" && keyExpr in item) {
    const keyVal = item[keyExpr];
    return keyVal !== void 0 && keyVal !== null ? keyVal : index;
  }
  return index;
}
function isDestructured(item) {
  return /^\[.*\]$/.test(item) || /^\{.*\}$/.test(item);
}
function extractVars(item) {
  return item.replace(/[\[\]\{\}]/g, "").split(",").map((i) => i.trim());
}
function esc(str) {
  return str.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}
function cleanup(state) {
  if (state.effectCleanup) {
    state.effectCleanup();
    state.effectCleanup = null;
  }
  state.lookup.forEach((loopEl) => loopEl.el.remove());
  state.lookup.clear();
  state.prevKeys.length = 0;
}
const ifStates = /* @__PURE__ */ new WeakMap();
let ifCounter = 0;
const If = {
  type: "attribute",
  name: "if",
  keyReq: "denied",
  valReq: "must",
  shouldEvaluate: true,
  returnsValue: true,
  onLoad: ({ el, effect: effect2, rx, runtimeErr: runtimeErr2, computed: computed2 }) => {
    if (!(el instanceof HTMLTemplateElement)) {
      throw runtimeErr2("IfMustBeOnTemplate", {
        message: "data-if must be used on <template> elements"
      });
    }
    const templateContent = el.content;
    const rootElements = Array.from(templateContent.children);
    if (rootElements.length !== 1) {
      throw runtimeErr2("IfTemplateMustHaveSingleRoot", {
        count: rootElements.length,
        message: "data-if template must contain exactly one root element"
      });
    }
    const template = el;
    const container = template.parentElement;
    template.style.display = "none";
    const ifId = `__if_${++ifCounter}`;
    const commentMarker = document.createComment(`Datastar if ${ifId}`);
    container.insertBefore(commentMarker, template.nextSibling);
    const state = {
      renderedElement: null,
      isRendered: false,
      commentMarker,
      effectCleanup: null,
      ifId
    };
    ifStates.set(el, state);
    try {
      const conditionComputed = computed2(() => {
        return rx();
      });
      const effectCleanup = effect2(() => {
        const shouldRender = !!conditionComputed();
        if (shouldRender && !state.isRendered) {
          state.renderedElement = renderElement(
            template,
            state.commentMarker
          );
          state.isRendered = true;
        } else if (!shouldRender && state.isRendered) {
          if (state.renderedElement) {
            cleanupElement(state.renderedElement);
            state.renderedElement = null;
          }
          state.isRendered = false;
        }
      });
      state.effectCleanup = effectCleanup;
      return () => {
        cleanupIfInstance(state);
        ifStates.delete(el);
      };
    } catch (error) {
      const instanceState = ifStates.get(el);
      if (instanceState) {
        cleanupIfInstance(instanceState);
        ifStates.delete(el);
      }
      throw error;
    }
  }
};
function renderElement(template, commentMarker) {
  const clone = template.content.cloneNode(true);
  const element = clone.firstElementChild;
  commentMarker.parentNode.insertBefore(element, commentMarker.nextSibling);
  queueMicrotask(() => {
    apply(element);
  });
  return element;
}
function cleanupElement(element) {
  element.remove();
}
function cleanupIfInstance(state) {
  if (state.effectCleanup) {
    state.effectCleanup();
    state.effectCleanup = null;
  }
  if (state.renderedElement) {
    cleanupElement(state.renderedElement);
    state.renderedElement = null;
  }
  if (state.commentMarker && state.commentMarker.parentNode) {
    state.commentMarker.remove();
  }
  state.isRendered = false;
}
const fetchAbortControllers = /* @__PURE__ */ new WeakMap();
const createHttpMethod = (name, method) => ({
  type: "action",
  name,
  fn: async (ctx, url2, args) => {
    const { el } = ctx;
    const requestCancellation = args?.requestCancellation ?? "auto";
    const controller = requestCancellation instanceof AbortController ? requestCancellation : new AbortController();
    const isDisabled = requestCancellation === "disabled";
    if (!isDisabled) {
      fetchAbortControllers.get(el)?.abort();
    }
    if (!isDisabled && !(requestCancellation instanceof AbortController)) {
      fetchAbortControllers.set(el, controller);
    }
    try {
      await fetcher(ctx, method, url2, args, controller.signal);
    } finally {
      if (fetchAbortControllers.get(el) === controller) {
        fetchAbortControllers.delete(el);
      }
    }
  }
});
const dispatchFetch = (type, el, argsRaw) => document.dispatchEvent(
  new CustomEvent(DATASTAR_FETCH_EVENT, {
    detail: { type, el, argsRaw }
  })
);
const isWrongContent = (err) => `${err}`.includes("text/event-stream");
const fetcher = async ({ el, evt, filtered: filtered2, runtimeErr: runtimeErr2 }, method, url2, {
  selector,
  headers: userHeaders,
  contentType = "json",
  filterSignals: { include = /.*/, exclude = /(^|\.)_/ } = {},
  openWhenHidden = false,
  retryInterval = DefaultSseRetryDurationMs,
  retryScaler = 2,
  retryMaxWaitMs = 3e4,
  retryMaxCount = 10
} = {}, abort) => {
  const action = method.toLowerCase();
  let cleanupFn = () => {
  };
  try {
    if (!url2?.length) {
      throw runtimeErr2("FetchNoUrlProvided", { action });
    }
    const initialHeaders = {
      Accept: "text/event-stream, text/html, application/json",
      [DATASTAR_REQUEST]: true
    };
    if (contentType === "json") {
      initialHeaders["Content-Type"] = "application/json";
    }
    const headers = Object.assign({}, initialHeaders, userHeaders);
    const req = {
      method,
      headers,
      openWhenHidden,
      retryInterval,
      retryScaler,
      retryMaxWaitMs,
      retryMaxCount,
      signal: abort,
      onopen: async (response) => {
        if (response.status >= 400)
          dispatchFetch(ERROR, el, {
            status: response.status.toString()
          });
      },
      onmessage: (evt2) => {
        if (!evt2.event.startsWith(DATASTAR)) return;
        const type = evt2.event;
        const argsRawLines = {};
        for (const line of evt2.data.split("\n")) {
          const i = line.indexOf(" ");
          const k = line.slice(0, i);
          const v = line.slice(i + 1);
          (argsRawLines[k] ||= []).push(v);
        }
        const argsRaw = Object.fromEntries(
          Object.entries(argsRawLines).map(([k, v]) => [
            k,
            v.join("\n")
          ])
        );
        dispatchFetch(type, el, argsRaw);
      },
      onerror: (error) => {
        if (isWrongContent(error)) {
          throw runtimeErr2("InvalidContentType", { url: url2 });
        }
        if (error) {
          console.error(error.message);
          dispatchFetch(RETRYING, el, { message: error.message });
        }
      }
    };
    const urlInstance = new URL(url2, window.location.href);
    const queryParams = new URLSearchParams(urlInstance.search);
    if (contentType === "json") {
      const res = JSON.stringify(filtered2({ include, exclude }));
      if (method === "GET") {
        queryParams.set(DATASTAR, res);
      } else {
        req.body = res;
      }
    } else if (contentType === "form") {
      const formEl = selector ? document.querySelector(selector) : el.closest("form");
      if (!formEl) {
        throw runtimeErr2(
          selector ? "FetchFormNotFound" : "FetchClosestFormNotFound",
          { action, selector }
        );
      }
      if (!formEl.checkValidity()) {
        formEl.reportValidity();
        cleanupFn();
        return;
      }
      const formData = new FormData(formEl);
      let submitter = el;
      if (el === formEl && evt instanceof SubmitEvent) {
        submitter = evt.submitter;
      } else {
        const preventDefault = (evt2) => evt2.preventDefault();
        formEl.addEventListener("submit", preventDefault);
        cleanupFn = () => formEl.removeEventListener("submit", preventDefault);
      }
      if (submitter instanceof HTMLButtonElement) {
        const name = submitter.getAttribute("name");
        if (name) formData.append(name, submitter.value);
      }
      const multipart = formEl.getAttribute("enctype") === "multipart/form-data";
      if (!multipart) {
        headers["Content-Type"] = "application/x-www-form-urlencoded";
      }
      const formParams = new URLSearchParams(formData);
      if (method === "GET") {
        for (const [key, value] of formParams) {
          queryParams.append(key, value);
        }
      } else if (multipart) {
        if (__USE_UPLOAD_PROGRESS__ && urlInstance.protocol === "https:") {
          const boundary = `----FormDataBoundary${Math.random().toString(36).substring(2, 11)}`;
          const encoder = new TextEncoder();
          let total = 0;
          const parts = [];
          for (const [name, value] of formData) {
            parts.push({ field: name, value });
            total += encoder.encode(`--${boundary}\r
`).byteLength;
            if (value instanceof File) {
              total += encoder.encode(
                `Content-Disposition: form-data; name="${name}"; filename="${value.name}"\r
Content-Type: ${value.type || "application/octet-stream"}\r
\r
`
              ).byteLength;
              total += value.size + 2;
            } else {
              total += encoder.encode(
                `Content-Disposition: form-data; name="${name}"\r
\r
${value}\r
`
              ).byteLength;
            }
          }
          total += encoder.encode(`--${boundary}--\r
`).byteLength;
          let loaded = 0;
          req.body = new ReadableStream({
            async start(controller) {
              const write = (data) => {
                controller.enqueue(data);
                loaded += data.byteLength;
                const progress = Math.round(
                  loaded / total * 100
                );
                dispatchFetch("upload-progress", el, {
                  progress: progress.toString(),
                  loaded: loaded.toString(),
                  total: total.toString()
                });
              };
              dispatchFetch("upload-progress", el, {
                progress: "0",
                loaded: "0",
                total: total.toString()
              });
              try {
                for (const { field, value } of parts) {
                  write(encoder.encode(`--${boundary}\r
`));
                  if (value instanceof File) {
                    write(
                      encoder.encode(
                        `Content-Disposition: form-data; name="${field}"; filename="${value.name}"\r
Content-Type: ${value.type || "application/octet-stream"}\r
\r
`
                      )
                    );
                    const reader = value.stream().getReader();
                    try {
                      while (true) {
                        const { done, value: chunk } = await reader.read();
                        if (done) break;
                        write(chunk);
                      }
                    } finally {
                      reader.releaseLock();
                    }
                    write(encoder.encode("\r\n"));
                  } else {
                    write(
                      encoder.encode(
                        `Content-Disposition: form-data; name="${field}"\r
\r
${value}\r
`
                      )
                    );
                  }
                }
                write(encoder.encode(`--${boundary}--\r
`));
                if (loaded < total) {
                  dispatchFetch("upload-progress", el, {
                    progress: "100",
                    loaded: total.toString(),
                    total: total.toString()
                  });
                }
                controller.close();
              } catch (error) {
                controller.error(error);
              }
            }
          });
          headers["Content-Type"] = `multipart/form-data; boundary=${boundary}`;
          req.duplex = "half";
        } else {
          req.body = formData;
        }
      } else {
        req.body = formParams;
      }
    } else {
      throw runtimeErr2("FetchInvalidContentType", {
        action,
        contentType
      });
    }
    dispatchFetch(STARTED, el, {});
    urlInstance.search = queryParams.toString();
    try {
      await fetchEventSource(urlInstance.toString(), el, req);
    } catch (error) {
      if (!isWrongContent(error)) {
        throw runtimeErr2("FetchFailed", { method, url: url2, error });
      }
    }
  } finally {
    dispatchFetch(FINISHED, el, {});
    cleanupFn();
  }
};
async function getBytes(stream, onChunk) {
  const reader = stream.getReader();
  let result = await reader.read();
  while (!result.done) {
    onChunk(result.value);
    result = await reader.read();
  }
}
function getLines(onLine) {
  let buffer;
  let position;
  let fieldLength;
  let discardTrailingNewline = false;
  return function onChunk(arr) {
    if (!buffer) {
      buffer = arr;
      position = 0;
      fieldLength = -1;
    } else {
      buffer = concat(buffer, arr);
    }
    const bufLength = buffer.length;
    let lineStart = 0;
    while (position < bufLength) {
      if (discardTrailingNewline) {
        if (buffer[position] === 10) lineStart = ++position;
        discardTrailingNewline = false;
      }
      let lineEnd = -1;
      for (; position < bufLength && lineEnd === -1; ++position) {
        switch (buffer[position]) {
          case 58:
            if (fieldLength === -1) {
              fieldLength = position - lineStart;
            }
            break;
          // @ts-ignore:7029 \r case below should fallthrough to \n:
          // biome-ignore lint/suspicious/noFallthroughSwitchClause: intentional fallthrough for CR to LF
          case 13:
            discardTrailingNewline = true;
          case 10:
            lineEnd = position;
            break;
        }
      }
      if (lineEnd === -1) break;
      onLine(buffer.subarray(lineStart, lineEnd), fieldLength);
      lineStart = position;
      fieldLength = -1;
    }
    if (lineStart === bufLength)
      buffer = void 0;
    else if (lineStart) {
      buffer = buffer.subarray(lineStart);
      position -= lineStart;
    }
  };
}
function getMessages(onId, onRetry, onMessage) {
  let message = newMessage();
  const decoder = new TextDecoder();
  return function onLine(line, fieldLength) {
    if (!line.length) {
      onMessage?.(message);
      message = newMessage();
    } else if (fieldLength > 0) {
      const field = decoder.decode(line.subarray(0, fieldLength));
      const valueOffset = fieldLength + (line[fieldLength + 1] === 32 ? 2 : 1);
      const value = decoder.decode(line.subarray(valueOffset));
      switch (field) {
        case "data":
          message.data = message.data ? `${message.data}
${value}` : value;
          break;
        case "event":
          message.event = value;
          break;
        case "id":
          onId(message.id = value);
          break;
        case "retry": {
          const retry = +value;
          if (!Number.isNaN(retry)) {
            onRetry(message.retry = retry);
          }
          break;
        }
      }
    }
  };
}
const concat = (a, b) => {
  const res = new Uint8Array(a.length + b.length);
  res.set(a);
  res.set(b, a.length);
  return res;
};
const newMessage = () => ({
  // data, event, and id must be initialized to empty strings:
  // https://html.spec.whatwg.org/multipage/server-sent-events.html#event-stream-interpretation
  // retry should be initialized to undefined so we return a consistent shape
  // to the js engine all the time: https://mathiasbynens.be/notes/shapes-ics#takeaways
  data: "",
  event: "",
  id: "",
  retry: void 0
});
function fetchEventSource(input, el, {
  signal: inputSignal,
  headers: inputHeaders,
  onopen: inputOnOpen,
  onmessage,
  onclose,
  onerror,
  openWhenHidden,
  fetch: inputFetch,
  retryInterval = 1e3,
  retryScaler = 2,
  retryMaxWaitMs = 3e4,
  retryMaxCount = 10,
  overrides,
  ...rest
}) {
  return new Promise((resolve, reject) => {
    const headers = {
      ...inputHeaders
    };
    let curRequestController;
    function onVisibilityChange() {
      curRequestController.abort();
      if (!document.hidden) create();
    }
    if (!openWhenHidden) {
      document.addEventListener("visibilitychange", onVisibilityChange);
    }
    let retryTimer = 0;
    function dispose() {
      document.removeEventListener(
        "visibilitychange",
        onVisibilityChange
      );
      window.clearTimeout(retryTimer);
      curRequestController.abort();
    }
    inputSignal?.addEventListener("abort", () => {
      dispose();
      resolve();
    });
    const fetch = inputFetch || window.fetch;
    const onopen = inputOnOpen || (() => {
    });
    let retries = 0;
    let baseRetryInterval = retryInterval;
    async function create() {
      curRequestController = new AbortController();
      try {
        const response = await fetch(input, {
          ...rest,
          headers,
          signal: curRequestController.signal
        });
        retries = 0;
        retryInterval = baseRetryInterval;
        await onopen(response);
        const dispatchNonSSE = async (dispatchType, response2, name, overrides2, ...argNames) => {
          const argsRaw = {
            [name]: await response2.text()
          };
          for (const n of argNames) {
            let v = response2.headers.get(`datastar-${kebab(n)}`);
            if (overrides2) {
              const o = overrides2[n];
              if (o)
                v = typeof o === "string" ? o : JSON.stringify(o);
            }
            if (v) argsRaw[n] = v;
          }
          dispatchFetch(dispatchType, el, argsRaw);
          dispose();
          resolve();
        };
        const ct = response.headers.get("Content-Type");
        if (ct?.includes("text/html")) {
          return await dispatchNonSSE(
            EventTypePatchElements,
            response,
            "elements",
            overrides,
            "selector",
            "mode",
            "useViewTransition"
          );
        }
        if (ct?.includes("application/json")) {
          return await dispatchNonSSE(
            EventTypePatchSignals,
            response,
            "signals",
            overrides,
            "onlyIfMissing"
          );
        }
        if (ct?.includes("text/javascript")) {
          const script = document.createElement("script");
          const scriptAttributesHeader = response.headers.get(
            "datastar-script-attributes"
          );
          if (scriptAttributesHeader) {
            for (const [name, value] of Object.entries(
              JSON.parse(scriptAttributesHeader)
            )) {
              script.setAttribute(name, value);
            }
          }
          script.textContent = await response.text();
          document.head.appendChild(script);
          dispose();
          return;
        }
        await getBytes(
          response.body,
          getLines(
            getMessages(
              (id) => {
                if (id) {
                  headers["last-event-id"] = id;
                } else {
                  delete headers["last-event-id"];
                }
              },
              (retry) => {
                baseRetryInterval = retryInterval = retry;
              },
              onmessage
            )
          )
        );
        onclose?.();
        dispose();
        resolve();
      } catch (err) {
        if (!curRequestController.signal.aborted) {
          try {
            const interval = onerror?.(err) || retryInterval;
            window.clearTimeout(retryTimer);
            retryTimer = window.setTimeout(create, interval);
            retryInterval = Math.min(
              retryInterval * retryScaler,
              retryMaxWaitMs
            );
            if (++retries >= retryMaxCount) {
              dispatchFetch(RETRIES_FAILED, el, {});
              dispose();
              reject("Max retries reached.");
            } else {
              console.error(
                `Datastar failed to reach ${input.toString()} retrying in ${interval}ms.`
              );
            }
          } catch (innerErr) {
            dispose();
            reject(innerErr);
          }
        }
      }
    }
    create();
  });
}
const DELETE = createHttpMethod("delete", "DELETE");
const GET = createHttpMethod("get", "GET");
const PATCH = createHttpMethod("patch", "PATCH");
const POST = createHttpMethod("post", "POST");
const PUT = createHttpMethod("put", "PUT");
function getCSRFToken() {
  const metaTag = document.querySelector('meta[name="csrf-token"]');
  return metaTag?.getAttribute("content") || null;
}
function hasManualCSRF(userHeaders = {}) {
  const csrfHeaders = ["X-CSRF-TOKEN", "x-csrf-token", "X-Csrf-Token"];
  return csrfHeaders.some((header) => userHeaders[header]);
}
function getCSRFHeaders(userHeaders = {}) {
  if (hasManualCSRF(userHeaders)) {
    return {};
  }
  const csrfToken = getCSRFToken();
  return csrfToken ? { "X-CSRF-TOKEN": csrfToken } : {};
}
const createHttpMethodWithCSRF = (name, method) => {
  const basePlugin = createHttpMethod(name, method);
  return {
    ...basePlugin,
    fn: async (ctx, url2, args) => {
      const enhancedArgs = {
        ...args,
        headers: {
          ...args?.headers,
          ...getCSRFHeaders(args?.headers)
        }
      };
      return basePlugin.fn(ctx, url2, enhancedArgs);
    }
  };
};
const POSTX = createHttpMethodWithCSRF("postx", "POST");
const PUTX = createHttpMethodWithCSRF("putx", "PUT");
const PATCHX = createHttpMethodWithCSRF("patchx", "PATCH");
const DELETEX = createHttpMethodWithCSRF("deletex", "DELETE");
const NavigateAction = {
  type: "action",
  name: "navigate",
  fn: (ctx, urlOrQueries, key = "true", options = {}) => {
    if (!urlOrQueries) {
      throw ctx.runtimeErr("NavigateUrlRequired", {
        received: String(urlOrQueries)
      });
    }
    if (typeof key !== "string") {
      throw ctx.runtimeErr("NavigateKeyMustBeString", {
        received: String(key)
      });
    }
    try {
      const finalUrl = processNavigationInput(urlOrQueries, options);
      if (typeof window.hyperNavigate !== "function") {
        console.error(
          "hyperNavigate is not available. Ensure GlobalNavigate watcher is loaded."
        );
        window.location.href = finalUrl;
        return;
      }
      window.hyperNavigate(finalUrl, key);
      if (options.replace) {
        setTimeout(() => {
          history.replaceState(null, "", finalUrl);
        }, 0);
      }
    } catch (error) {
      console.error("Navigate action failed:", error);
      const fallbackUrl = typeof urlOrQueries === "string" ? urlOrQueries : `${window.location.pathname}?${buildQueryString$1(
        urlOrQueries
      )}`;
      window.location.href = fallbackUrl;
    }
  }
};
function processNavigationInput(urlOrQueries, options) {
  if (typeof urlOrQueries === "string") {
    return processStringUrl(urlOrQueries, options);
  } else {
    return processJsonQueries(urlOrQueries, options);
  }
}
function processStringUrl(url2, options) {
  if (options.queries && Object.keys(options.queries).length > 0) {
    url2 = mergeQueriesIntoUrl(url2, options.queries);
  }
  if (shouldApplyMerge(options)) {
    return mergeQueryParameters(url2, options.only, options.except);
  }
  return url2;
}
function processJsonQueries(queries, options) {
  const currentPath = window.location.pathname;
  const queryString = buildQueryString$1(queries);
  const baseUrl = queryString ? `${currentPath}?${queryString}` : currentPath;
  if (shouldApplyMerge(options)) {
    return mergeQueryParameters(baseUrl, options.only, options.except);
  }
  return baseUrl;
}
function shouldApplyMerge(options) {
  if (options.merge !== void 0) {
    return options.merge;
  }
  if (options.only || options.except) {
    return true;
  }
  return false;
}
function buildQueryString$1(queries) {
  const params = new URLSearchParams();
  for (const [key, value] of Object.entries(queries)) {
    if (value === null || value === void 0) {
      continue;
    }
    if (Array.isArray(value)) {
      value.forEach((item) => {
        if (item !== null && item !== void 0 && item !== "") {
          params.append(key, String(item));
        }
      });
    } else if (value === "" || String(value).trim() === "") {
      continue;
    } else {
      params.set(key, String(value));
    }
  }
  return params.toString();
}
function mergeQueriesIntoUrl(url2, additionalQueries) {
  try {
    let targetUrl;
    if (url2.startsWith("?")) {
      targetUrl = new URL(
        `${window.location.pathname}${url2}`,
        window.location.origin
      );
    } else if (url2.startsWith("http")) {
      targetUrl = new URL(url2);
    } else {
      targetUrl = new URL(url2, window.location.origin);
    }
    for (const [key, value] of Object.entries(additionalQueries)) {
      if (value === null || value === void 0 || value === "") {
        targetUrl.searchParams.delete(key);
      } else if (Array.isArray(value)) {
        targetUrl.searchParams.delete(key);
        value.forEach((item) => {
          if (item !== null && item !== void 0 && item !== "") {
            targetUrl.searchParams.append(key, String(item));
          }
        });
      } else {
        targetUrl.searchParams.set(key, String(value));
      }
    }
    return `${targetUrl.pathname}${targetUrl.search}`;
  } catch (error) {
    console.warn("Error merging queries into URL:", error);
    return url2;
  }
}
function mergeQueryParameters(url2, only, except) {
  try {
    const currentParams = new URLSearchParams(window.location.search);
    let targetUrl;
    let targetPath;
    let targetParams;
    if (url2.startsWith("?")) {
      targetPath = window.location.pathname;
      targetParams = new URLSearchParams(url2);
    } else if (url2.startsWith("http")) {
      targetUrl = new URL(url2);
      targetPath = targetUrl.pathname;
      targetParams = targetUrl.searchParams;
    } else {
      const baseUrl = new URL(window.location.origin);
      targetUrl = new URL(url2, baseUrl);
      targetPath = targetUrl.pathname;
      targetParams = targetUrl.searchParams;
    }
    const mergedParams = new URLSearchParams();
    for (const [key, value] of currentParams) {
      const shouldInclude = only ? only.includes(key) : !except || !except.includes(key);
      if (shouldInclude) {
        mergedParams.set(key, value);
      }
    }
    for (const [key, value] of targetParams) {
      if (!value || value.trim() === "") {
        mergedParams.delete(key);
      } else {
        mergedParams.set(key, value);
      }
    }
    const queryString = mergedParams.toString();
    return queryString ? `${targetPath}?${queryString}` : targetPath;
  } catch (error) {
    console.warn("Error merging query parameters:", error);
    return url2;
  }
}
const PatchElements = {
  type: "watcher",
  name: EventTypePatchElements,
  async onGlobalInit(ctx) {
    datastarSSEEventWatcher(EventTypePatchElements, (args) => {
      if (supportsViewTransitions && args.useViewTransition?.trim() === "true") {
        document.startViewTransition(() => onPatchElements(ctx, args));
      } else {
        onPatchElements(ctx, args);
      }
    });
  }
};
function onPatchElements(ctx, {
  elements = "",
  selector,
  mode = DefaultElementPatchMode
}) {
  const { initErr: initErr2 } = ctx;
  const elementsWithSvgsRemoved = elements.replace(
    /<svg(\s[^>]*>|>)([\s\S]*?)<\/svg>/gim,
    ""
  );
  const hasHtml = /<\/html>/.test(elementsWithSvgsRemoved);
  const hasHead = /<\/head>/.test(elementsWithSvgsRemoved);
  const hasBody = /<\/body>/.test(elementsWithSvgsRemoved);
  const newDocument = new DOMParser().parseFromString(
    hasHtml || hasHead || hasBody ? elements : `<body><template>${elements}</template></body>`,
    "text/html"
  );
  let newContent = document.createDocumentFragment();
  if (hasHtml) {
    newContent.appendChild(newDocument.documentElement);
  } else if (hasHead && hasBody) {
    newContent.appendChild(newDocument.head);
    newContent.appendChild(newDocument.body);
  } else if (hasHead) {
    newContent.appendChild(newDocument.head);
  } else if (hasBody) {
    newContent.appendChild(newDocument.body);
  } else {
    newContent = newDocument.querySelector("template").content;
  }
  if (!selector && (mode === ElementPatchModeOuter || mode === ElementPatchModeReplace)) {
    for (const child of newContent.children) {
      let target;
      if (child instanceof HTMLHtmlElement) {
        target = document.documentElement;
      } else if (child instanceof HTMLBodyElement) {
        target = document.body;
      } else if (child instanceof HTMLHeadElement) {
        target = document.head;
      } else {
        target = document.getElementById(child.id);
        if (!target) {
          console.error(
            initErr2("NoTargetsFound", {
              id: child.id
            })
          );
          continue;
        }
      }
      applyToTargets(ctx, mode, child, [target]);
    }
  } else {
    const targets = document.querySelectorAll(selector);
    if (!targets.length) {
      console.error(
        initErr2("NoTargetsFound", {
          selector
        })
      );
      return;
    }
    applyToTargets(ctx, mode, newContent, targets);
  }
}
const scripts = /* @__PURE__ */ new WeakSet();
for (const script of document.querySelectorAll("script")) {
  scripts.add(script);
}
function execute(target) {
  const elScripts = target instanceof HTMLScriptElement ? [target] : target.querySelectorAll("script");
  for (const old of elScripts) {
    if (!scripts.has(old)) {
      const script = document.createElement("script");
      for (const { name, value } of old.attributes) {
        script.setAttribute(name, value);
      }
      script.text = old.text;
      old.replaceWith(script);
      scripts.add(script);
    }
  }
}
function applyToTargets({ initErr: initErr2 }, mode, element, capturedTargets) {
  for (const target of capturedTargets) {
    const cloned = element.cloneNode(true);
    if (mode === ElementPatchModeRemove) {
      target.remove();
    } else if (mode === ElementPatchModeOuter || mode === ElementPatchModeInner) {
      morph(target, cloned, mode);
      execute(target);
    } else {
      execute(cloned);
      if (mode === ElementPatchModeReplace) {
        target.replaceWith(cloned);
      } else if (mode === ElementPatchModePrepend) {
        target.prepend(cloned);
      } else if (mode === ElementPatchModeAppend) {
        target.append(cloned);
      } else if (mode === ElementPatchModeBefore) {
        target.before(cloned);
      } else if (mode === ElementPatchModeAfter) {
        target.after(cloned);
      } else {
        throw initErr2("InvalidPatchMode", { mode });
      }
    }
  }
}
const oldIdTagNameMap = /* @__PURE__ */ new Map();
const ctxIdMap = /* @__PURE__ */ new Map();
const ctxPersistentIds = /* @__PURE__ */ new Set();
const duplicateIds = /* @__PURE__ */ new Set();
const ctxPantry = document.createElement("div");
ctxPantry.hidden = true;
function morph(oldElt, newContent, mode) {
  const ignore = aliasify("ignore-morph");
  if (oldElt.hasAttribute(ignore) && newContent instanceof HTMLElement && newContent.hasAttribute(ignore) || oldElt.parentElement?.closest(`[${ignore}]`)) {
    return;
  }
  const normalizedElt = document.createElement("div");
  normalizedElt.append(newContent);
  document.body.insertAdjacentElement("afterend", ctxPantry);
  const oldIdElements = oldElt.querySelectorAll("[id]");
  for (const { id, tagName } of oldIdElements) {
    if (oldIdTagNameMap.has(id)) {
      duplicateIds.add(id);
    } else {
      oldIdTagNameMap.set(id, tagName);
    }
  }
  if (oldElt.id) {
    if (oldIdTagNameMap.has(oldElt.id)) {
      duplicateIds.add(oldElt.id);
    } else {
      oldIdTagNameMap.set(oldElt.id, oldElt.tagName);
    }
  }
  ctxPersistentIds.clear();
  const newIdElements = normalizedElt.querySelectorAll("[id]");
  for (const { id, tagName } of newIdElements) {
    if (ctxPersistentIds.has(id)) {
      duplicateIds.add(id);
    } else if (oldIdTagNameMap.get(id) === tagName) {
      ctxPersistentIds.add(id);
    }
  }
  oldIdTagNameMap.clear();
  for (const id of duplicateIds) {
    ctxPersistentIds.delete(id);
  }
  duplicateIds.clear();
  ctxIdMap.clear();
  populateIdMapWithTree(
    mode === "outer" ? oldElt.parentElement : oldElt,
    oldIdElements
  );
  populateIdMapWithTree(normalizedElt, newIdElements);
  morphChildren(
    mode === "outer" ? oldElt.parentElement : oldElt,
    normalizedElt,
    mode === "outer" ? oldElt : null,
    oldElt.nextSibling
  );
  ctxPantry.remove();
}
function morphChildren(oldParent, newParent, insertionPoint = null, endPoint = null) {
  if (oldParent instanceof HTMLTemplateElement && newParent instanceof HTMLTemplateElement) {
    oldParent = oldParent.content;
    newParent = newParent.content;
  }
  insertionPoint ??= oldParent.firstChild;
  for (const newChild of newParent.childNodes) {
    if (insertionPoint && insertionPoint !== endPoint) {
      const bestMatch = findBestMatch(newChild, insertionPoint, endPoint);
      if (bestMatch) {
        if (bestMatch !== insertionPoint) {
          let cursor = insertionPoint;
          while (cursor && cursor !== bestMatch) {
            const tempNode = cursor;
            cursor = cursor.nextSibling;
            removeNode(tempNode);
          }
        }
        morphNode(bestMatch, newChild);
        insertionPoint = bestMatch.nextSibling;
        continue;
      }
    }
    const ncId = newChild.id;
    if (newChild instanceof Element && ctxPersistentIds.has(ncId)) {
      const movedChild = window[ncId];
      let current = movedChild;
      while (current = current.parentNode) {
        const idSet = ctxIdMap.get(current);
        if (idSet) {
          idSet.delete(ncId);
          if (!idSet.size) {
            ctxIdMap.delete(current);
          }
        }
      }
      moveBefore(oldParent, movedChild, insertionPoint);
      morphNode(movedChild, newChild);
      insertionPoint = movedChild.nextSibling;
      continue;
    }
    if (ctxIdMap.has(newChild)) {
      const newEmptyChild = document.createElement(
        newChild.tagName
      );
      oldParent.insertBefore(newEmptyChild, insertionPoint);
      morphNode(newEmptyChild, newChild);
      insertionPoint = newEmptyChild.nextSibling;
    } else {
      const newClonedChild = document.importNode(newChild, true);
      oldParent.insertBefore(newClonedChild, insertionPoint);
      insertionPoint = newClonedChild.nextSibling;
    }
  }
  while (insertionPoint && insertionPoint !== endPoint) {
    const tempNode = insertionPoint;
    insertionPoint = insertionPoint.nextSibling;
    removeNode(tempNode);
  }
}
function findBestMatch(node, startPoint, endPoint) {
  let bestMatch = null;
  let nextSibling = node.nextSibling;
  let siblingSoftMatchCount = 0;
  let displaceMatchCount = 0;
  const nodeMatchCount = ctxIdMap.get(node)?.size || 0;
  let cursor = startPoint;
  while (cursor && cursor !== endPoint) {
    if (isSoftMatch(cursor, node)) {
      let isIdSetMatch = false;
      const oldSet = ctxIdMap.get(cursor);
      const newSet = ctxIdMap.get(node);
      if (newSet && oldSet) {
        for (const id of oldSet) {
          if (newSet.has(id)) {
            isIdSetMatch = true;
            break;
          }
        }
      }
      if (isIdSetMatch) {
        return cursor;
      }
      if (!bestMatch && !ctxIdMap.has(cursor)) {
        if (!nodeMatchCount) {
          return cursor;
        }
        bestMatch = cursor;
      }
    }
    displaceMatchCount += ctxIdMap.get(cursor)?.size || 0;
    if (displaceMatchCount > nodeMatchCount) {
      break;
    }
    if (bestMatch === null && nextSibling && isSoftMatch(cursor, nextSibling)) {
      siblingSoftMatchCount++;
      nextSibling = nextSibling.nextSibling;
      if (siblingSoftMatchCount >= 2) {
        bestMatch = void 0;
      }
    }
    if (cursor.contains(document.activeElement)) break;
    cursor = cursor.nextSibling;
  }
  return bestMatch || null;
}
function isSoftMatch(oldNode, newNode) {
  const oldId = oldNode.id;
  return oldNode.nodeType === newNode.nodeType && oldNode.tagName === newNode.tagName && // If oldElt has an `id` with possible state and it doesn’t match newElt.id then avoid morphing.
  // We'll still match an anonymous node with an IDed newElt, though, because if it got this far,
  // its not persistent, and new nodes can't have any hidden state.
  (!oldId || oldId === newNode.id);
}
function removeNode(node) {
  if (ctxIdMap.has(node)) {
    moveBefore(ctxPantry, node, null);
  } else {
    node.parentNode?.removeChild(node);
  }
}
const moveBefore = (
  // @ts-ignore
  removeNode.call.bind(ctxPantry.moveBefore ?? ctxPantry.insertBefore)
);
function morphNode(oldNode, newNode) {
  const type = newNode.nodeType;
  if (type === 1) {
    const ignore = aliasify("ignore-morph");
    if (oldNode.hasAttribute(ignore) && newNode.hasAttribute(ignore)) {
      return oldNode;
    }
    if (oldNode instanceof HTMLInputElement && newNode instanceof HTMLInputElement && newNode.type !== "file") {
      if (newNode.getAttribute("value") !== oldNode.getAttribute("value")) {
        oldNode.value = newNode.getAttribute("value") ?? "";
      }
    } else if (oldNode instanceof HTMLTextAreaElement && newNode instanceof HTMLTextAreaElement) {
      const newValue = newNode.value;
      if (newValue !== oldNode.value) {
        oldNode.value = newValue;
      }
      if (oldNode.firstChild && oldNode.firstChild.nodeValue !== newValue) {
        oldNode.firstChild.nodeValue = newValue;
      }
    }
    const preserveAttrs = (newNode.getAttribute(aliasify("preserve-attr")) ?? "").split(" ");
    for (const { name, value } of newNode.attributes) {
      if (oldNode.getAttribute(name) !== value && !preserveAttrs.includes(kebab(name))) {
        oldNode.setAttribute(name, value);
      }
    }
    const oldAttrs = oldNode.attributes;
    for (let i = oldAttrs.length - 1; i >= 0; i--) {
      const { name } = oldAttrs[i];
      if (!newNode.hasAttribute(name) && !preserveAttrs.includes(kebab(name))) {
        oldNode.removeAttribute(name);
      }
    }
  }
  if (type === 8 || type === 3) {
    if (oldNode.nodeValue !== newNode.nodeValue) {
      oldNode.nodeValue = newNode.nodeValue;
    }
  }
  if (!oldNode.isEqualNode(newNode)) {
    morphChildren(oldNode, newNode);
  }
  return oldNode;
}
function populateIdMapWithTree(root2, elements) {
  for (const elt of elements) {
    if (ctxPersistentIds.has(elt.id)) {
      let current = elt;
      while (current && current !== root2) {
        let idSet = ctxIdMap.get(current);
        if (!idSet) {
          idSet = /* @__PURE__ */ new Set();
          ctxIdMap.set(current, idSet);
        }
        idSet.add(elt.id);
        current = current.parentElement;
      }
    }
  }
}
const PatchSignals = {
  type: "watcher",
  name: EventTypePatchSignals,
  onGlobalInit: (ctx) => datastarSSEEventWatcher(
    EventTypePatchSignals,
    ({
      signals: raw = "{}",
      onlyIfMissing: onlyIfMissingRaw = `${DefaultPatchSignalsOnlyIfMissing}`
    }) => ctx.mergePatch(jsStrToObject(raw), {
      ifMissing: isBoolString(onlyIfMissingRaw)
    })
  )
};
let popstateInitialized = false;
const PopstateHandler = {
  type: "watcher",
  name: "popstateHandler",
  onGlobalInit: () => {
    if (!popstateInitialized) {
      popstateInitialized = true;
      window.addEventListener("popstate", function(event) {
        const hasNavigateElements = document.querySelector("[data-navigate]");
        if (!hasNavigateElements) {
          window.location.reload();
          return;
        }
        if (typeof window.hyperNavigate === "function") {
          const navigationKey = event.state?.navigationKey || "popstate";
          window.hyperNavigate(
            window.location.href,
            navigationKey
          );
        } else {
          window.location.reload();
        }
      });
    }
  }
};
let globalNavigateSetup = false;
const GlobalNavigate = {
  type: "watcher",
  name: "globalNavigate",
  onGlobalInit: (ctx) => {
    if (!globalNavigateSetup) {
      globalNavigateSetup = true;
      setupEnhancedGlobalNavigation(ctx);
    }
  }
};
function setupEnhancedGlobalNavigation(ctx) {
  const { actions: actions2, startBatch: startBatch2, endBatch: endBatch2 } = ctx;
  const navigateWithOptions = (url2, key = "true", options = {}) => {
    try {
      startBatch2();
      const getAction = actions2.get || actions2.GET;
      if (!getAction) {
        throw new Error(
          "GET action not found in Datastar actions registry"
        );
      }
      const fetchArgs = {
        headers: {
          "HYPER-NAVIGATE": "true",
          "HYPER-NAVIGATE-KEY": key
        }
      };
      const runtimeCtx = {
        ...ctx,
        el: document.body
      };
      getAction.fn(runtimeCtx, url2, fetchArgs);
      setTimeout(() => {
        if (options.replace) {
          history.replaceState(null, "", url2);
        } else {
          history.pushState(null, "", url2);
        }
      }, 0);
    } catch (error) {
      console.error("Enhanced navigate failed:", error);
      window.location.href = url2;
    } finally {
      endBatch2();
    }
  };
  window.hyperNavigate = (url2, key = "true") => {
    navigateWithOptions(url2, key, {});
  };
  window.hyperNavigateWithOptions = navigateWithOptions;
  window.hyperNavigateWith = (url2, key = "true", merge = false, options = {}) => {
    navigateWithOptions(url2, key, { ...options, merge });
  };
  window.hyperNavigateMerge = (url2, key = "true", options = {}) => {
    navigateWithOptions(url2, key, { ...options, merge: true });
  };
  window.hyperNavigateClean = (url2, key = "true", options = {}) => {
    navigateWithOptions(url2, key, { ...options, merge: false });
  };
  window.hyperNavigateOnly = (url2, only, key = "true") => {
    navigateWithOptions(url2, key, { merge: true, only });
  };
  window.hyperNavigateExcept = (url2, except, key = "true") => {
    navigateWithOptions(url2, key, { merge: true, except });
  };
  window.hyperNavigateReplace = (url2, key = "true", options = {}) => {
    navigateWithOptions(url2, key, { ...options, replace: true });
  };
  window.hyperBackWithOptions = (fallbackUrl = "/", key = "back", options = {}) => {
    try {
      let backUrl = fallbackUrl;
      if (document.referrer && document.referrer !== window.location.href) {
        const referrerUrl = new URL(document.referrer);
        const currentUrl = new URL(window.location.href);
        if (referrerUrl.origin === currentUrl.origin) {
          backUrl = document.referrer;
        }
      }
      navigateWithOptions(backUrl, key, options);
    } catch (error) {
      console.error("Enhanced hyperBack failed:", error);
      if (history.length > 1) {
        history.back();
      } else {
        window.location.href = fallbackUrl;
      }
    }
  };
  window.hyperRefreshWithOptions = (key = "refresh", options = {}) => {
    try {
      const currentUrl = window.location.href;
      navigateWithOptions(currentUrl, key, options);
    } catch (error) {
      console.error("Enhanced hyperRefresh failed:", error);
      window.location.reload();
    }
  };
  window.hyperUpdateQueries = (queries, key = "update", merge = true) => {
    const currentPath = window.location.pathname;
    const queryString = buildQueryString(queries);
    const url2 = queryString ? `${currentPath}?${queryString}` : currentPath;
    navigateWithOptions(url2, key, { merge });
  };
  window.hyperClearQueries = (paramNames, key = "clear") => {
    const clearQueries = paramNames.reduce((acc, name) => {
      acc[name] = null;
      return acc;
    }, {});
    window.hyperUpdateQueries(clearQueries, key, true);
  };
  window.hyperResetPagination = (key = "pagination") => {
    window.hyperUpdateQueries({ page: 1 }, key, true);
  };
  window.hyperBack = (fallbackUrl = "/", key = "back") => {
    window.hyperBackWithOptions(fallbackUrl, key, { merge: true });
  };
  window.hyperRefresh = (key = "refresh") => {
    window.hyperRefreshWithOptions(key, { merge: true });
  };
  window.hyperReload = () => {
    window.location.reload();
  };
  window.hyperDebugNavigation = () => {
    console.group("🧭 Hyper Navigation Debug");
    console.log("Current URL:", window.location.href);
    console.log("Current Path:", window.location.pathname);
    console.log("Current Query:", window.location.search);
    console.log(
      "Current Queries:",
      Object.fromEntries(new URLSearchParams(window.location.search))
    );
    console.log("Referrer:", document.referrer);
    console.log("History Length:", history.length);
    console.groupEnd();
  };
}
function buildQueryString(queries) {
  const params = new URLSearchParams();
  for (const [key, value] of Object.entries(queries)) {
    if (value === null || value === void 0) {
      continue;
    }
    if (Array.isArray(value)) {
      value.forEach((item) => {
        if (item !== null && item !== void 0 && item !== "") {
          params.append(key, String(item));
        }
      });
    } else if (value === "" || String(value).trim() === "") {
      continue;
    } else {
      params.set(key, String(value));
    }
  }
  return params.toString();
}
const Navigate = {
  type: "attribute",
  name: "navigate",
  keyReq: "denied",
  valReq: "must",
  shouldEvaluate: false,
  onLoad: (ctx) => {
    const { el, value, mods, runtimeErr: runtimeErr2 } = ctx;
    const navigateConfig = parseNavigateModifiers(mods, value.trim());
    if (!navigateConfig) {
      throw runtimeErr2("InvalidNavigateConfiguration", {
        value,
        modifiers: Array.from(mods.keys())
      });
    }
    const executeNavigation = navigateConfig.timing ? createTimingWrapper(handleNavigation, navigateConfig.timing) : handleNavigation;
    const handleClick = (event) => {
      const target = event.target;
      const link2 = target.closest("a[href]");
      if (!link2 || !shouldInterceptNavigation(link2)) {
        return;
      }
      event.preventDefault();
      executeNavigation(link2.href, navigateConfig);
    };
    const handleSubmit = (event) => {
      const form = event.target;
      if (!shouldInterceptFormSubmit(form)) {
        return;
      }
      event.preventDefault();
      const formData = new FormData(form);
      const url2 = new URL(form.action, window.location.href);
      for (const [key, value2] of formData.entries()) {
        if (typeof value2 === "string") {
          url2.searchParams.set(key, value2);
        }
      }
      executeNavigation(url2.toString(), navigateConfig);
    };
    el.addEventListener("click", handleClick);
    el.addEventListener("submit", handleSubmit);
    return () => {
      el.removeEventListener("click", handleClick);
      el.removeEventListener("submit", handleSubmit);
    };
  }
};
function parseNavigateModifiers(mods, value) {
  if (!value || value.trim() === "") {
    return null;
  }
  const config = {
    key: "true",
    // Default key
    merge: false,
    // EXPLICIT: No more smart defaults!
    replace: false
  };
  for (const [modName, modTags] of mods) {
    switch (modName) {
      case "key":
        config.key = modTags.size > 0 ? Array.from(modTags)[0] : "true";
        break;
      case "merge":
        config.merge = true;
        break;
      case "only":
        config.only = Array.from(modTags);
        config.merge = true;
        break;
      case "except":
        config.except = Array.from(modTags);
        config.merge = true;
        break;
      case "replace":
        config.replace = true;
        break;
      case "debounce":
        config.timing = parseTimingModifier("debounce", modTags);
        break;
      case "throttle":
        config.timing = parseTimingModifier("throttle", modTags);
        break;
      case "delay":
        config.timing = parseTimingModifier("delay", modTags);
        break;
    }
  }
  if (config.only && config.except) {
    console.warn(
      "Navigate: Cannot use both __only and __except modifiers. Using __only."
    );
    delete config.except;
  }
  return config;
}
function parseTimingModifier(type, tags) {
  const tagArray = Array.from(tags);
  const durationTag = tagArray.find((tag) => /^\d+(?:ms|s)?$/.test(tag));
  if (!durationTag) {
    console.warn(
      `Navigate: Invalid ${type} timing - no duration specified`
    );
    return void 0;
  }
  let duration;
  if (durationTag.endsWith("ms")) {
    duration = parseInt(durationTag.slice(0, -2));
  } else if (durationTag.endsWith("s")) {
    duration = parseInt(durationTag.slice(0, -1)) * 1e3;
  } else {
    duration = parseInt(durationTag);
  }
  return {
    type,
    duration,
    leading: tagArray.includes("leading")
  };
}
function createTimingWrapper(fn, timing) {
  switch (timing.type) {
    case "debounce":
      return createDebounce(fn, timing.duration, timing.leading);
    case "throttle":
      return createThrottle(fn, timing.duration, timing.leading);
    case "delay":
      return createDelay(fn, timing.duration);
    default:
      return fn;
  }
}
function createDebounce(fn, delay2, leading) {
  let timeout;
  let hasRun = false;
  return (...args) => {
    const callNow = leading && !hasRun;
    clearTimeout(timeout);
    timeout = setTimeout(() => {
      hasRun = false;
      if (!leading) fn(...args);
    }, delay2);
    if (callNow) {
      hasRun = true;
      fn(...args);
    }
  };
}
function createThrottle(fn, limit, leading) {
  let inThrottle = false;
  let lastArgs = null;
  return (...args) => {
    if (!inThrottle) {
      if (leading !== false) {
        fn(...args);
      }
      inThrottle = true;
      setTimeout(() => {
        inThrottle = false;
        if (lastArgs && leading === false) {
          fn(...lastArgs);
          lastArgs = null;
        }
      }, limit);
    } else {
      lastArgs = args;
    }
  };
}
function createDelay(fn, delay2) {
  return (...args) => {
    setTimeout(() => fn(...args), delay2);
  };
}
function handleNavigation(url2, config) {
  try {
    const finalUrl = processUrlWithMergeConfig(url2, config);
    if (typeof window.hyperNavigate !== "function") {
      console.error(
        "hyperNavigate not available. Falling back to standard navigation."
      );
      window.location.href = finalUrl;
      return;
    }
    window.hyperNavigate(finalUrl, config.key);
    if (config.replace) {
      history.replaceState(null, "", finalUrl);
    } else {
      setTimeout(() => {
        if (window.location.href !== finalUrl) {
          history.pushState(null, "", finalUrl);
        }
      }, 0);
    }
  } catch (error) {
    console.error("Navigation failed:", error);
    window.location.href = url2;
  }
}
function processUrlWithMergeConfig(url2, config) {
  if (!config.merge) {
    return url2;
  }
  try {
    const currentParams = new URLSearchParams(window.location.search);
    let targetUrl;
    let targetPath;
    let targetParams;
    if (url2.startsWith("?")) {
      targetPath = window.location.pathname;
      targetParams = new URLSearchParams(url2);
    } else if (url2.startsWith("http")) {
      targetUrl = new URL(url2);
      targetPath = targetUrl.pathname;
      targetParams = targetUrl.searchParams;
    } else {
      const baseUrl = new URL(window.location.origin);
      targetUrl = new URL(url2, baseUrl);
      targetPath = targetUrl.pathname;
      targetParams = targetUrl.searchParams;
    }
    const mergedParams = new URLSearchParams();
    for (const [key, value] of currentParams) {
      const shouldInclude = config.only ? config.only.includes(key) : !config.except || !config.except.includes(key);
      if (shouldInclude) {
        mergedParams.set(key, value);
      }
    }
    for (const [key, value] of targetParams) {
      if (!value || value.trim() === "") {
        mergedParams.delete(key);
      } else {
        mergedParams.set(key, value);
      }
    }
    const queryString = mergedParams.toString();
    return queryString ? `${targetPath}?${queryString}` : targetPath;
  } catch (error) {
    console.warn("Error processing URL merge:", error);
    return url2;
  }
}
function shouldInterceptNavigation(link2) {
  try {
    const url2 = new URL(link2.href, window.location.href);
    if (url2.origin !== window.location.origin) {
      return false;
    }
  } catch {
    return false;
  }
  if (link2.hasAttribute("download")) {
    return false;
  }
  if (link2.hasAttribute("data-navigate-skip")) {
    return false;
  }
  return true;
}
function shouldInterceptFormSubmit(form) {
  if (form.method.toLowerCase() !== "get") {
    return false;
  }
  if (form.hasAttribute("data-navigate-skip")) {
    return false;
  }
  return true;
}
load(
  // Backend actions
  GET,
  POST,
  PUT,
  PATCH,
  DELETE,
  // Backend actions - Laravel with CSRF
  POSTX,
  PUTX,
  PATCHX,
  DELETEX,
  NavigateAction,
  // Backend watchers
  ResponseInterceptor,
  PatchElements,
  PatchSignals,
  PopstateHandler,
  GlobalNavigate,
  // Attributes
  Attr,
  Bind,
  Class,
  Computed,
  Effect,
  Error$1,
  For,
  If,
  Indicator,
  JsonSignals,
  Navigate,
  On,
  OnIntersect,
  OnInterval,
  OnLoad,
  OnSignalPatch,
  Ref,
  Show,
  Signals,
  Style,
  Text,
  // Actions
  Dispatch,
  Peek,
  SetAll,
  ToggleAll,
  FileUrl
);
apply();
export {
  apply,
  load,
  setAlias
};
//# sourceMappingURL=hyper.js.map
