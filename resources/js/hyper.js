const lol = /🖕JS_DS🚀/.source;
const DSP = lol.slice(0, 5);
const DSS = lol.slice(4);
const DATASTAR_FETCH_EVENT = "datastar-fetch";
const DATASTAR_SIGNAL_PATCH_EVENT = "datastar-signal-patch";
const kebab = (str) => str.replace(/([a-z0-9])([A-Z])/g, "$1-$2").replace(/([a-z])([0-9]+)/gi, "$1-$2").replace(/([0-9]+)([a-z])/gi, "$1-$2").toLowerCase();
const snake = (str) => kebab(str).replace(/-/g, "_");
const jsStrToObject = (raw) => {
  try {
    return JSON.parse(raw);
  } catch {
    return Function(`return (${raw})`)();
  }
};
const caseFns = {
  camel: (str) => str.replace(/-[a-z]/g, (x) => x[1].toUpperCase()),
  snake: (str) => str.replace(/-/g, "_"),
  pascal: (str) => str[0].toUpperCase() + caseFns.camel(str.slice(1))
};
const modifyCasing = (str, mods, defaultCase = "camel") => {
  for (const c of mods.get("case") || [defaultCase]) {
    str = caseFns[c]?.(str) || str;
  }
  return str;
};
const aliasify = (name) => `data-${name}`;
const hasOwn = (
  // @ts-expect-error
  Object.hasOwn ?? Object.prototype.hasOwnProperty.call
);
const isPojo = (obj) => obj !== null && typeof obj === "object" && (Object.getPrototypeOf(obj) === Object.prototype || Object.getPrototypeOf(obj) === null);
const isEmpty = (obj) => {
  for (const prop in obj) {
    if (hasOwn(obj, prop)) {
      return false;
    }
  }
  return true;
};
const updateLeaves = (obj, fn) => {
  for (const key in obj) {
    const val = obj[key];
    if (isPojo(val) || Array.isArray(val)) {
      updateLeaves(val, fn);
    } else {
      obj[key] = fn(val);
    }
  }
};
const pathToObj = (paths) => {
  const result = {};
  for (const [path, value] of paths) {
    const keys = path.split(".");
    const lastKey = keys.pop();
    const obj = keys.reduce((acc, key) => acc[key] ??= {}, result);
    obj[lastKey] = value;
  }
  return result;
};
const currentPatch = [];
const queuedEffects = [];
let batchDepth = 0;
let notifyIndex = 0;
let queuedEffectsLength = 0;
let prevSub;
let activeSub;
let version = 0;
const beginBatch = () => {
  batchDepth++;
};
const endBatch = () => {
  if (!--batchDepth) {
    flush();
    dispatch();
  }
};
const startPeeking = (sub) => {
  prevSub = activeSub;
  activeSub = sub;
};
const stopPeeking = () => {
  activeSub = prevSub;
  prevSub = void 0;
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
  startPeeking(e);
  beginBatch();
  try {
    e.fn_();
  } finally {
    endBatch();
    stopPeeking();
  }
  return effectOper.bind(0, e);
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
const updateComputed = (c) => {
  startPeeking(c);
  startTracking(c);
  try {
    const oldValue = c.value_;
    return oldValue !== (c.value_ = c.getter(oldValue));
  } finally {
    stopPeeking();
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
    startPeeking(e);
    startTracking(e);
    beginBatch();
    try {
      e.fn_();
    } finally {
      endBatch();
      stopPeeking();
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
const signalOper = (s, ...value) => {
  if (value.length) {
    if (s.value_ !== (s.value_ = value[0])) {
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
  const nextDep = prevDep ? prevDep.nextDep_ : sub.deps_;
  if (nextDep && nextDep.dep_ === dep) {
    nextDep.version_ = version;
    sub.depsTail_ = nextDep;
    return;
  }
  const prevSub2 = dep.subsTail_;
  if (prevSub2 && prevSub2.version_ === version && prevSub2.sub_ === sub) {
    return;
  }
  const newLink = sub.depsTail_ = dep.subsTail_ = {
    version_: version,
    dep_: dep,
    sub_: sub,
    prevDep_: prevDep,
    nextDep_: nextDep,
    prevSub_: prevSub2
  };
  if (nextDep) {
    nextDep.prevDep_ = newLink;
  }
  if (prevDep) {
    prevDep.nextDep_ = newLink;
  } else {
    sub.deps_ = newLink;
  }
  if (prevSub2) {
    prevSub2.nextSub_ = newLink;
  } else {
    dep.subs_ = newLink;
  }
};
const unlink = (link2, sub = link2.sub_) => {
  const dep_ = link2.dep_;
  const prevDep_ = link2.prevDep_;
  const nextDep_ = link2.nextDep_;
  const nextSub_ = link2.nextSub_;
  const prevSub_ = link2.prevSub_;
  if (nextDep_) {
    nextDep_.prevDep_ = prevDep_;
  } else {
    sub.depsTail_ = prevDep_;
  }
  if (prevDep_) {
    prevDep_.nextDep_ = nextDep_;
  } else {
    sub.deps_ = nextDep_;
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
        const nextSub = (link2 = subSubs).nextSub_;
        if (nextSub) {
          stack = { value_: next, prev_: stack };
          next = nextSub;
        }
        continue;
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
  version++;
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
  let dirty = false;
  top: while (true) {
    const dep = link2.dep_;
    const flags = dep.flags_;
    if (sub.flags_ & 16) {
      dirty = true;
    } else if ((flags & 17) === 17) {
      if (update(dep)) {
        const subs = dep.subs_;
        if (subs.nextSub_) {
          shallowPropagate(subs);
        }
        dirty = true;
      }
    } else if ((flags & 33) === 33) {
      if (link2.nextSub_ || link2.prevSub_) {
        stack = { value_: link2, prev_: stack };
      }
      link2 = dep.deps_;
      sub = dep;
      ++checkDepth;
      continue;
    }
    if (!dirty) {
      const nextDep = link2.nextDep_;
      if (nextDep) {
        link2 = nextDep;
        continue;
      }
    }
    while (checkDepth--) {
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
        dirty = false;
      } else {
        sub.flags_ &= -33;
      }
      sub = link2.sub_;
      if (link2.nextDep_) {
        link2 = link2.nextDep_;
        continue top;
      }
    }
    return dirty;
  }
};
const shallowPropagate = (link2) => {
  do {
    const sub = link2.sub_;
    const flags = sub.flags_;
    if ((flags & 48) === 32) {
      sub.flags_ = flags | 16;
      if (flags & 2) {
        notify(sub);
      }
    }
  } while (link2 = link2.nextSub_);
};
const isValidLink = (checkLink, sub) => {
  let link2 = sub.depsTail_;
  while (link2) {
    if (link2 === checkLink) {
      return true;
    }
    link2 = link2.prevDep_;
  }
  return false;
};
const getPath = (path) => {
  let result = root;
  const split = path.split(".");
  for (const path2 of split) {
    if (result == null || !hasOwn(result, path2)) {
      return;
    }
    result = result[path2];
  }
  return result;
};
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
      get(_, prop) {
        if (!(prop === "toJSON" && !hasOwn(deepObj, prop))) {
          if (isArr && prop in Array.prototype) {
            keys();
            return deepObj[prop];
          }
          if (typeof prop === "symbol") {
            return deepObj[prop];
          }
          if (!hasOwn(deepObj, prop) || deepObj[prop]() == null) {
            deepObj[prop] = signal("");
            dispatch(prefix + prop, "");
            keys(keys() + 1);
          }
          return deepObj[prop]();
        }
      },
      set(_, prop, newValue) {
        const path = prefix + prop;
        if (isArr && prop === "length") {
          const diff = deepObj[prop] - newValue;
          deepObj[prop] = newValue;
          if (diff > 0) {
            const patch = {};
            for (let i = newValue; i < deepObj[prop]; i++) {
              patch[i] = null;
            }
            dispatch(prefix.slice(0, -1), patch);
            keys(keys() + 1);
          }
        } else if (hasOwn(deepObj, prop)) {
          if (newValue == null) {
            delete deepObj[prop];
          } else if (hasOwn(newValue, computedSymbol)) {
            deepObj[prop] = newValue;
            dispatch(path, "");
          } else if (deepObj[prop](deep(newValue, `${path}.`))) {
            dispatch(path, newValue);
          }
        } else if (newValue != null) {
          if (hasOwn(newValue, computedSymbol)) {
            deepObj[prop] = newValue;
            dispatch(path, "");
          } else {
            deepObj[prop] = signal(deep(newValue, `${path}.`));
            dispatch(path, newValue);
          }
          keys(keys() + 1);
        }
        return true;
      },
      deleteProperty(_, prop) {
        delete deepObj[prop];
        keys(keys() + 1);
        return true;
      },
      ownKeys() {
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
const dispatch = (path, value) => {
  if (path !== void 0 && value !== void 0) {
    currentPatch.push([path, value]);
  }
  if (!batchDepth && currentPatch.length) {
    const detail = pathToObj(currentPatch);
    currentPatch.length = 0;
    document.dispatchEvent(
      new CustomEvent(DATASTAR_SIGNAL_PATCH_EVENT, {
        detail
      })
    );
  }
};
const mergePatch = (patch, { ifMissing } = {}) => {
  beginBatch();
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
const mergePaths = (paths, options) => mergePatch(pathToObj(paths), options);
const mergeInner = (patch, target, targetParent, prefix, ifMissing) => {
  if (isPojo(patch)) {
    if (!(hasOwn(targetParent, target) && (isPojo(targetParent[target]) || Array.isArray(targetParent[target])))) {
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
  } else if (!(ifMissing && hasOwn(targetParent, target))) {
    targetParent[target] = patch;
  }
};
const toRegExp = (val) => typeof val === "string" ? RegExp(val.replace(/^\/|\/$/g, "")) : val;
const filtered = ({ include = /.*/, exclude = /(?!)/ } = {}, obj = root) => {
  const includeRe = toRegExp(include);
  const excludeRe = toRegExp(exclude);
  const paths = [];
  const stack = [[obj, ""]];
  while (stack.length) {
    const [node, prefix] = stack.pop();
    for (const key in node) {
      const path = prefix + key;
      if (isPojo(node[key])) {
        stack.push([node[key], `${path}.`]);
      } else if (includeRe.test(path) && !excludeRe.test(path)) {
        paths.push([path, getPath(path)]);
      }
    }
  }
  return pathToObj(paths);
};
const root = deep({});
const isHTMLOrSVG = (el) => el instanceof HTMLElement || el instanceof SVGElement || el instanceof MathMLElement;
const url = "https://data-star.dev/errors";
const error = (ctx, reason, metadata = {}) => {
  Object.assign(metadata, ctx);
  const e = new Error();
  const r = snake(reason);
  const q = new URLSearchParams({
    metadata: JSON.stringify(metadata)
  }).toString();
  const c = JSON.stringify(metadata, null, 2);
  e.message = `${reason}
More info: ${url}/${r}?${q}
Context: ${c}`;
  return e;
};
const actionPlugins = /* @__PURE__ */ new Map();
const attributePlugins = /* @__PURE__ */ new Map();
const watcherPlugins = /* @__PURE__ */ new Map();
const actions = new Proxy(
  {},
  {
    get: (_, prop) => actionPlugins.get(prop)?.apply,
    has: (_, prop) => actionPlugins.has(prop),
    ownKeys: () => Reflect.ownKeys(actionPlugins),
    set: () => false,
    deleteProperty: () => false
  }
);
const removals = /* @__PURE__ */ new Map();
const queuedAttributes = [];
const queuedAttributeNames = /* @__PURE__ */ new Set();
const attribute = (plugin) => {
  queuedAttributes.push(plugin);
  if (queuedAttributes.length === 1) {
    setTimeout(() => {
      for (const attribute2 of queuedAttributes) {
        queuedAttributeNames.add(attribute2.name);
        attributePlugins.set(attribute2.name, attribute2);
      }
      queuedAttributes.length = 0;
      apply();
      queuedAttributeNames.clear();
    });
  }
};
const action = (plugin) => {
  actionPlugins.set(plugin.name, plugin);
};
document.addEventListener(DATASTAR_FETCH_EVENT, (evt) => {
  const plugin = watcherPlugins.get(evt.detail.type);
  if (plugin) {
    plugin.apply(
      {
        error: error.bind(0, {
          plugin: { type: "watcher", name: plugin.name },
          element: {
            id: evt.target.id,
            tag: evt.target.tagName
          }
        })
      },
      evt.detail.argsRaw
    );
  }
});
const watcher = (plugin) => {
  watcherPlugins.set(plugin.name, plugin);
};
const cleanupEls = (els) => {
  for (const el of els) {
    const cleanups = removals.get(el);
    if (removals.delete(el)) {
      for (const cleanup2 of cleanups.values()) {
        cleanup2();
      }
      cleanups.clear();
    }
  }
};
const aliasedIgnore = aliasify("ignore");
const aliasedIgnoreAttr = `[${aliasedIgnore}]`;
const shouldIgnore = (el) => el.hasAttribute(`${aliasedIgnore}__self`) || !!el.closest(aliasedIgnoreAttr);
const applyEls = (els, onlyNew) => {
  for (const el of els) {
    if (!shouldIgnore(el)) {
      for (const key in el.dataset) {
        applyAttributePlugin(
          el,
          key.replace(/[A-Z]/g, "-$&").toLowerCase(),
          el.dataset[key],
          onlyNew
        );
      }
    }
  }
};
const observe = (mutations) => {
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
    } else if (type === "attributes" && attributeName.startsWith("data-") && isHTMLOrSVG(target) && !shouldIgnore(target)) {
      const key = attributeName.slice(5);
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
};
const mutationObserver = new MutationObserver(observe);
const apply = (root2 = document.documentElement) => {
  if (isHTMLOrSVG(root2)) {
    applyEls([root2], true);
  }
  applyEls(root2.querySelectorAll("*"), true);
  mutationObserver.observe(root2, {
    subtree: true,
    childList: true,
    attributes: true
  });
};
const applyAttributePlugin = (el, attrKey, value, onlyNew) => {
  {
    const rawKey = attrKey;
    const [namePart, ...rawModifiers] = rawKey.split("__");
    const [pluginName, key] = namePart.split(/:(.+)/);
    const plugin = attributePlugins.get(pluginName);
    if ((!onlyNew || queuedAttributeNames.has(pluginName)) && plugin) {
      const ctx = {
        el,
        rawKey,
        mods: /* @__PURE__ */ new Map(),
        error: error.bind(0, {
          plugin: { type: "attribute", name: plugin.name },
          element: { id: el.id, tag: el.tagName },
          expression: { rawKey, key, value }
        }),
        key,
        value,
        rx: void 0
      };
      const keyReq = plugin.requirement && (typeof plugin.requirement === "string" ? plugin.requirement : plugin.requirement.key) || "allowed";
      const valueReq = plugin.requirement && (typeof plugin.requirement === "string" ? plugin.requirement : plugin.requirement.value) || "allowed";
      if (key) {
        if (keyReq === "denied") {
          throw ctx.error("KeyNotAllowed");
        }
      } else if (keyReq === "must") {
        throw ctx.error("KeyRequired");
      }
      if (value) {
        if (valueReq === "denied") {
          throw ctx.error("ValueNotAllowed");
        }
      } else if (valueReq === "must") {
        throw ctx.error("ValueRequired");
      }
      if (keyReq === "exclusive" || valueReq === "exclusive") {
        if (key && value) {
          throw ctx.error("KeyAndValueProvided");
        }
        if (!key && !value) {
          throw ctx.error("KeyOrValueRequired");
        }
      }
      if (value) {
        let cachedRx;
        ctx.rx = (...args) => {
          if (!cachedRx) {
            cachedRx = genRx(value, {
              returnsValue: plugin.returnsValue,
              argNames: plugin.argNames
            });
          }
          return cachedRx(el, ...args);
        };
      }
      for (const rawMod of rawModifiers) {
        const [label, ...mod] = rawMod.split(".");
        ctx.mods.set(label, new Set(mod));
      }
      const cleanup2 = plugin.apply(ctx);
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
};
const genRx = (value, { returnsValue = false, argNames = [] } = {}) => {
  let expr = "";
  if (returnsValue) {
    const statementRe = /(\/(\\\/|[^/])*\/|"(\\"|[^"])*"|'(\\'|[^'])*'|`(\\`|[^`])*`|\(\s*((function)\s*\(\s*\)|(\(\s*\))\s*=>)\s*(?:\{[\s\S]*?\}|[^;){]*)\s*\)\s*\(\s*\)|[^;])+/gm;
    const statements = value.trim().match(statementRe);
    if (statements) {
      const lastIdx = statements.length - 1;
      const last = statements[lastIdx].trim();
      if (!last.startsWith("return")) {
        statements[lastIdx] = `return (${last});`;
      }
      expr = statements.join(";\n");
    }
  } else {
    expr = value.trim();
  }
  const escaped = /* @__PURE__ */ new Map();
  const escapeRe = RegExp(`(?:${DSP})(.*?)(?:${DSS})`, "gm");
  let counter = 0;
  for (const match of expr.matchAll(escapeRe)) {
    const k = match[1];
    const v = `__escaped${counter++}`;
    escaped.set(v, k);
    expr = expr.replace(DSP + k + DSS, v);
  }
  expr = expr.replace(/\$\['([a-zA-Z_$\d][\w$]*)'\]/g, "$$$1").replace(
    /\$([a-zA-Z_\d]\w*(?:[.-]\w+)*)/g,
    (_, signalName) => signalName.split(".").reduce((acc, part) => `${acc}['${part}']`, "$")
  ).replace(
    /\[(\$[a-zA-Z_\d]\w*)\]/g,
    (_, varName) => `[$['${varName.slice(1)}']]`
  );
  expr = expr.replaceAll(/@(\w+)\(/g, '__action("$1",evt,');
  for (const [k, v] of escaped) {
    expr = expr.replace(k, v);
  }
  try {
    const fn = Function("el", "$", "__action", "evt", ...argNames, expr);
    return (el, ...args) => {
      const action2 = (name, evt, ...args2) => {
        const err = error.bind(0, {
          plugin: { type: "action", name },
          element: { id: el.id, tag: el.tagName },
          expression: {
            fnContent: expr,
            value
          }
        });
        const fn2 = actions[name];
        if (fn2) {
          return fn2(
            {
              el,
              evt,
              error: err
            },
            ...args2
          );
        }
        throw err("UndefinedAction");
      };
      try {
        return fn(el, root, action2, void 0, ...args);
      } catch (e) {
        console.error(e);
        throw error(
          {
            element: { id: el.id, tag: el.tagName },
            expression: {
              fnContent: expr,
              value
            },
            error: e.message
          },
          "ExecuteExpression"
        );
      }
    };
  } catch (e) {
    console.error(e);
    throw error(
      {
        expression: {
          fnContent: expr,
          value
        },
        error: e.message
      },
      "GenerateExpression"
    );
  }
};
action({
  name: "peek",
  apply(_, fn) {
    startPeeking();
    try {
      return fn();
    } finally {
      stopPeeking();
    }
  }
});
action({
  name: "setAll",
  apply(_, value, filter) {
    startPeeking();
    const masked = filtered(filter);
    updateLeaves(masked, () => value);
    mergePatch(masked);
    stopPeeking();
  }
});
action({
  name: "toggleAll",
  apply(_, filter) {
    startPeeking();
    const masked = filtered(filter);
    updateLeaves(masked, (oldValue) => !oldValue);
    mergePatch(masked);
    stopPeeking();
  }
});
const fetchAbortControllers = /* @__PURE__ */ new WeakMap();
const createHttpMethod = (name, method) => action({
  name,
  apply: async ({ el, evt, error: error2 }, url2, {
    selector,
    headers: userHeaders,
    contentType = "json",
    filterSignals: { include = /.*/, exclude = /(^|\.)_/ } = {},
    openWhenHidden = false,
    retryInterval = 1e3,
    retryScaler = 2,
    retryMaxWaitMs = 3e4,
    retryMaxCount = 10,
    requestCancellation = "auto"
  } = {}) => {
    const controller = requestCancellation instanceof AbortController ? requestCancellation : new AbortController();
    const isDisabled = requestCancellation === "disabled";
    if (!isDisabled) {
      const oldController = fetchAbortControllers.get(el);
      if (oldController) {
        oldController.abort();
        await Promise.resolve();
      }
    }
    if (!isDisabled && !(requestCancellation instanceof AbortController)) {
      fetchAbortControllers.set(el, controller);
    }
    try {
      const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
          for (const removed of mutation.removedNodes) {
            if (removed === el) {
              controller.abort();
              cleanupFn();
            }
          }
        }
      });
      if (el.parentNode) {
        observer.observe(el.parentNode, { childList: true });
      }
      let cleanupFn = () => {
        observer.disconnect();
      };
      try {
        if (!url2?.length) {
          throw error2("FetchNoUrlProvided", { action });
        }
        const initialHeaders = {
          Accept: "text/event-stream, text/html, application/json",
          "Datastar-Request": true
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
          signal: controller.signal,
          onopen: async (response) => {
            if (response.status >= 400)
              dispatchFetch(ERROR, el, { status: response.status.toString() });
          },
          onmessage: (evt2) => {
            if (!evt2.event.startsWith("datastar")) return;
            const type = evt2.event;
            const argsRawLines = {};
            for (const line of evt2.data.split("\n")) {
              const i = line.indexOf(" ");
              const k = line.slice(0, i);
              const v = line.slice(i + 1);
              (argsRawLines[k] ||= []).push(v);
            }
            const argsRaw = Object.fromEntries(
              Object.entries(argsRawLines).map(([k, v]) => [k, v.join("\n")])
            );
            dispatchFetch(type, el, argsRaw);
          },
          onerror: (error22) => {
            if (isWrongContent(error22)) {
              throw error22("FetchExpectedTextEventStream", { url: url2 });
            }
            if (error22) {
              console.error(error22.message);
              dispatchFetch(RETRYING, el, { message: error22.message });
            }
          }
        };
        const urlInstance = new URL(url2, document.baseURI);
        const queryParams = new URLSearchParams(urlInstance.search);
        if (contentType === "json") {
          const res = JSON.stringify(filtered({ include, exclude }));
          if (method === "GET") {
            queryParams.set("datastar", res);
          } else {
            req.body = res;
          }
        } else if (contentType === "form") {
          const formEl = selector ? document.querySelector(selector) : el.closest("form");
          if (!formEl) {
            throw error2("FetchFormNotFound", { action, selector });
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
            cleanupFn = () => {
              formEl.removeEventListener("submit", preventDefault);
              observer.disconnect();
            };
          }
          if (submitter instanceof HTMLButtonElement) {
            const name2 = submitter.getAttribute("name");
            if (name2) formData.append(name2, submitter.value);
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
            req.body = formData;
          } else {
            req.body = formParams;
          }
        } else {
          throw error2("FetchInvalidContentType", { action, contentType });
        }
        dispatchFetch(STARTED, el, {});
        urlInstance.search = queryParams.toString();
        try {
          await fetchEventSource(urlInstance.toString(), el, req);
        } catch (e) {
          if (!isWrongContent(e)) {
            throw error2("FetchFailed", { method, url: url2, error: e.message });
          }
        }
      } finally {
        dispatchFetch(FINISHED, el, {});
        cleanupFn();
      }
    } finally {
      if (fetchAbortControllers.get(el) === controller) {
        fetchAbortControllers.delete(el);
      }
    }
  }
});
createHttpMethod("delete", "DELETE");
createHttpMethod("get", "GET");
createHttpMethod("patch", "PATCH");
createHttpMethod("post", "POST");
createHttpMethod("put", "PUT");
const STARTED = "started";
const FINISHED = "finished";
const ERROR = "error";
const RETRYING = "retrying";
const RETRIES_FAILED = "retries-failed";
const dispatchFetch = (type, el, argsRaw) => document.dispatchEvent(
  new CustomEvent(DATASTAR_FETCH_EVENT, {
    detail: { type, el, argsRaw }
  })
);
const isWrongContent = (err) => `${err}`.includes("text/event-stream");
const getBytes = async (stream, onChunk) => {
  const reader = stream.getReader();
  let result = await reader.read();
  while (!result.done) {
    onChunk(result.value);
    result = await reader.read();
  }
};
const getLines = (onLine) => {
  let buffer;
  let position;
  let fieldLength;
  let discardTrailingNewline = false;
  return (arr) => {
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
          // @ts-expect-error:7029 \r case below should fallthrough to \n:
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
};
const getMessages = (onId, onRetry, onMessage) => {
  let message = newMessage();
  const decoder = new TextDecoder();
  return (line, fieldLength) => {
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
};
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
const fetchEventSource = (input, el, {
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
}) => {
  return new Promise((resolve, reject) => {
    const headers = {
      ...inputHeaders
    };
    let curRequestController;
    const onVisibilityChange = () => {
      curRequestController.abort();
      if (!document.hidden) create();
    };
    if (!openWhenHidden) {
      document.addEventListener("visibilitychange", onVisibilityChange);
    }
    let retryTimer = 0;
    const dispose = () => {
      document.removeEventListener("visibilitychange", onVisibilityChange);
      clearTimeout(retryTimer);
      curRequestController.abort();
    };
    inputSignal?.addEventListener("abort", () => {
      dispose();
      resolve();
    });
    const fetch = inputFetch || window.fetch;
    const onopen = inputOnOpen || (() => {
    });
    let retries = 0;
    let baseRetryInterval = retryInterval;
    const create = async () => {
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
              if (o) v = typeof o === "string" ? o : JSON.stringify(o);
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
            "datastar-patch-elements",
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
            "datastar-patch-signals",
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
            clearTimeout(retryTimer);
            retryTimer = setTimeout(create, interval);
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
    };
    create();
  });
};
attribute({
  name: "attr",
  requirement: { value: "must" },
  returnsValue: true,
  apply({ el, key, rx }) {
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
    const cleanup2 = effect(update2);
    return () => {
      observer.disconnect();
      cleanup2();
    };
  }
});
const dataURIRegex = /^data:(?<mime>[^;]+);base64,(?<contents>.*)$/;
const empty = Symbol("empty");
const aliasedBind = aliasify("bind");
attribute({
  name: "bind",
  requirement: "exclusive",
  apply({ el, key, mods, value, error: error2 }) {
    const signalName = key != null ? modifyCasing(key, mods) : value;
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
            const signalFiles = [];
            Promise.all(
              files.map(
                (f) => new Promise((resolve) => {
                  const reader = new FileReader();
                  reader.onload = () => {
                    if (typeof reader.result !== "string") {
                      throw error2("InvalidFileResultType", {
                        resultType: typeof reader.result
                      });
                    }
                    const match = reader.result.match(dataURIRegex);
                    if (!match?.groups) {
                      throw error2("InvalidDataUri", {
                        result: reader.result
                      });
                    }
                    signalFiles.push({
                      name: f.name,
                      contents: match.groups.contents,
                      mime: match.groups.mime
                    });
                  };
                  reader.onloadend = () => resolve();
                  reader.readAsDataURL(f);
                })
              )
            ).then(() => {
              mergePaths([[signalName, signalFiles]]);
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
    const initialValue = getPath(signalName);
    const type = typeof initialValue;
    let path = signalName;
    if (Array.isArray(initialValue) && !(el instanceof HTMLSelectElement && el.multiple)) {
      const signalNameKebab = key ? key : value;
      const inputs = document.querySelectorAll(
        `[${aliasedBind}\\:${CSS.escape(signalNameKebab)}],[${aliasedBind}="${CSS.escape(signalNameKebab)}"]`
      );
      const paths = [];
      let i = 0;
      for (const input of inputs) {
        paths.push([`${path}.${i}`, get(input, "none")]);
        if (el === input) {
          break;
        }
        i++;
      }
      mergePaths(paths, { ifMissing: true });
      path = `${path}.${i}`;
    } else {
      mergePaths([[path, get(el, type)]], {
        ifMissing: true
      });
    }
    const syncSignal = () => {
      const signalValue = getPath(path);
      if (signalValue != null) {
        const value2 = get(el, typeof signalValue);
        if (value2 !== empty) {
          mergePaths([[path, value2]]);
        }
      }
    };
    el.addEventListener("input", syncSignal);
    el.addEventListener("change", syncSignal);
    const cleanup2 = effect(() => {
      set(getPath(path));
    });
    return () => {
      cleanup2();
      el.removeEventListener("input", syncSignal);
      el.removeEventListener("change", syncSignal);
    };
  }
});
attribute({
  name: "class",
  requirement: {
    value: "must"
  },
  returnsValue: true,
  apply({ key, el, mods, rx }) {
    if (key) {
      key = modifyCasing(key, mods, "kebab");
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
    const cleanup2 = effect(callback);
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
});
attribute({
  name: "computed",
  requirement: {
    value: "must"
  },
  returnsValue: true,
  apply({ key, mods, rx, error: error2 }) {
    if (key) {
      mergePaths([[modifyCasing(key, mods), computed(rx)]]);
    } else {
      const patch = Object.assign({}, rx());
      updateLeaves(patch, (old) => {
        if (typeof old === "function") {
          return computed(old);
        } else {
          throw error2("ComputedExpectedFunction");
        }
      });
      mergePatch(patch);
    }
  }
});
attribute({
  name: "effect",
  requirement: {
    key: "denied",
    value: "must"
  },
  apply: ({ rx }) => effect(rx)
});
attribute({
  name: "indicator",
  requirement: "exclusive",
  apply({ el, key, mods, value }) {
    const signalName = key != null ? modifyCasing(key, mods) : value;
    mergePaths([[signalName, false]]);
    const watcher2 = (event) => {
      const { type, el: elt } = event.detail;
      if (elt !== el) {
        return;
      }
      switch (type) {
        case STARTED:
          mergePaths([[signalName, true]]);
          break;
        case FINISHED:
          mergePaths([[signalName, false]]);
          break;
      }
    };
    document.addEventListener(DATASTAR_FETCH_EVENT, watcher2);
    return () => {
      mergePaths([[signalName, false]]);
      document.removeEventListener(DATASTAR_FETCH_EVENT, watcher2);
    };
  }
});
attribute({
  name: "json-signals",
  requirement: {
    key: "denied"
  },
  apply({ el, value, mods }) {
    const spaces = mods.has("terse") ? 0 : 2;
    let filters = {};
    if (value) {
      filters = jsStrToObject(value);
    }
    const callback = () => {
      observer.disconnect();
      el.textContent = JSON.stringify(filtered(filters), null, spaces);
      observer.observe(el, {
        childList: true,
        characterData: true,
        subtree: true
      });
    };
    const observer = new MutationObserver(callback);
    const cleanup2 = effect(callback);
    return () => {
      observer.disconnect();
      cleanup2();
    };
  }
});
const tagToMs = (args) => {
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
};
const tagHas = (tags, tag, defaultValue = false) => {
  if (!tags) return defaultValue;
  return tags.has(tag.toLowerCase());
};
const delay = (callback, wait) => {
  return (...args) => {
    setTimeout(() => {
      callback(...args);
    }, wait);
  };
};
const debounce = (callback, wait, leading = false, trailing = true) => {
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
      timer = 0;
    }, wait);
  };
};
const throttle = (callback, wait, leading = true, trailing = false) => {
  let waiting = false;
  return (...args) => {
    if (waiting) return;
    if (leading) {
      callback(...args);
    }
    waiting = true;
    setTimeout(() => {
      if (trailing) {
        callback(...args);
      }
      waiting = false;
    }, wait);
  };
};
const modifyTiming = (callback, mods) => {
  const delayArgs = mods.get("delay");
  if (delayArgs) {
    const wait = tagToMs(delayArgs);
    callback = delay(callback, wait);
  }
  const debounceArgs = mods.get("debounce");
  if (debounceArgs) {
    const wait = tagToMs(debounceArgs);
    const leading = tagHas(debounceArgs, "leading", false);
    const trailing = !tagHas(debounceArgs, "notrailing", false);
    callback = debounce(callback, wait, leading, trailing);
  }
  const throttleArgs = mods.get("throttle");
  if (throttleArgs) {
    const wait = tagToMs(throttleArgs);
    const leading = !tagHas(throttleArgs, "noleading", false);
    const trailing = tagHas(throttleArgs, "trailing", false);
    callback = throttle(callback, wait, leading, trailing);
  }
  return callback;
};
const supportsViewTransitions = !!document.startViewTransition;
const modifyViewTransition = (callback, mods) => {
  if (mods.has("viewtransition") && supportsViewTransitions) {
    const cb = callback;
    callback = (...args) => document.startViewTransition(() => cb(...args));
  }
  return callback;
};
attribute({
  name: "on",
  requirement: "must",
  argNames: ["evt"],
  apply({ el, key, mods, rx }) {
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
      }
      beginBatch();
      rx(evt);
      endBatch();
    };
    callback = modifyViewTransition(callback, mods);
    callback = modifyTiming(callback, mods);
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
    const eventName = modifyCasing(key, mods, "kebab");
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
});
const once = /* @__PURE__ */ new WeakSet();
attribute({
  name: "on-intersect",
  requirement: {
    key: "denied",
    value: "must"
  },
  apply({ el, mods, rx }) {
    let callback = () => {
      beginBatch();
      rx();
      endBatch();
    };
    callback = modifyViewTransition(callback, mods);
    callback = modifyTiming(callback, mods);
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
});
attribute({
  name: "on-interval",
  requirement: {
    key: "denied",
    value: "must"
  },
  apply({ mods, rx }) {
    let callback = () => {
      beginBatch();
      rx();
      endBatch();
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
});
attribute({
  name: "init",
  requirement: {
    key: "denied",
    value: "must"
  },
  apply({ rx, mods }) {
    let callback = () => {
      beginBatch();
      rx();
      endBatch();
    };
    callback = modifyViewTransition(callback, mods);
    let wait = 0;
    const delayArgs = mods.get("delay");
    if (delayArgs) {
      wait = tagToMs(delayArgs);
      if (wait > 0) {
        callback = delay(callback, wait);
      }
    }
    callback();
  }
});
attribute({
  name: "on-signal-patch",
  requirement: {
    value: "must"
  },
  argNames: ["patch"],
  returnsValue: true,
  apply({ el, key, mods, rx, error: error2 }) {
    if (!!key && key !== "filter") {
      throw error2("KeyNotAllowed");
    }
    const filtersRaw = el.getAttribute("data-on-signal-patch-filter");
    let filters = {};
    if (filtersRaw) {
      filters = jsStrToObject(filtersRaw);
    }
    const callback = modifyTiming(
      (evt) => {
        const watched = filtered(filters, evt.detail);
        if (!isEmpty(watched)) {
          beginBatch();
          rx(watched);
          endBatch();
        }
      },
      mods
    );
    document.addEventListener(DATASTAR_SIGNAL_PATCH_EVENT, callback);
    return () => {
      document.removeEventListener(DATASTAR_SIGNAL_PATCH_EVENT, callback);
    };
  }
});
attribute({
  name: "ref",
  requirement: "exclusive",
  apply({ el, key, mods, value }) {
    const signalName = key != null ? modifyCasing(key, mods) : value;
    mergePaths([[signalName, el]]);
  }
});
const NONE = "none";
const DISPLAY = "display";
attribute({
  name: "show",
  requirement: {
    key: "denied",
    value: "must"
  },
  returnsValue: true,
  apply({ el, rx }) {
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
    const cleanup2 = effect(update2);
    return () => {
      observer.disconnect();
      cleanup2();
    };
  }
});
attribute({
  name: "signals",
  returnsValue: true,
  apply({ key, mods, rx }) {
    const ifMissing = mods.has("ifmissing");
    if (key) {
      key = modifyCasing(key, mods);
      mergePaths([[key, rx?.()]], { ifMissing });
    } else {
      const patch = Object.assign({}, rx?.());
      mergePatch(patch, { ifMissing });
    }
  }
});
attribute({
  name: "style",
  requirement: {
    value: "must"
  },
  returnsValue: true,
  apply({ key, el, rx }) {
    const { style } = el;
    const initialStyles = /* @__PURE__ */ new Map();
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
    const cleanup2 = effect(update2);
    return () => {
      observer.disconnect();
      cleanup2();
      for (const [prop, initial] of initialStyles) {
        initial ? style.setProperty(prop, initial) : style.removeProperty(prop);
      }
    };
  }
});
attribute({
  name: "text",
  requirement: {
    key: "denied",
    value: "must"
  },
  returnsValue: true,
  apply({ el, rx }) {
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
    const cleanup2 = effect(update2);
    return () => {
      observer.disconnect();
      cleanup2();
    };
  }
});
const ctxIdMap = /* @__PURE__ */ new Map();
const ctxPersistentIds = /* @__PURE__ */ new Set();
const oldIdTagNameMap = /* @__PURE__ */ new Map();
const duplicateIds = /* @__PURE__ */ new Set();
const ctxPantry = document.createElement("div");
ctxPantry.hidden = true;
const aliasedIgnoreMorph = aliasify("ignore-morph");
const aliasedIgnoreMorphAttr = `[${aliasedIgnoreMorph}]`;
const morph = (oldElt, newContent, mode = "outer") => {
  if (isHTMLOrSVG(oldElt) && isHTMLOrSVG(newContent) && oldElt.hasAttribute(aliasedIgnoreMorph) && newContent.hasAttribute(aliasedIgnoreMorph) || oldElt.parentElement?.closest(aliasedIgnoreMorphAttr)) {
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
  if (oldElt instanceof Element && oldElt.id) {
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
  for (const id of duplicateIds) {
    ctxPersistentIds.delete(id);
  }
  oldIdTagNameMap.clear();
  duplicateIds.clear();
  ctxIdMap.clear();
  const parent = mode === "outer" ? oldElt.parentElement : oldElt;
  populateIdMapWithTree(parent, oldIdElements);
  populateIdMapWithTree(normalizedElt, newIdElements);
  morphChildren(
    parent,
    normalizedElt,
    mode === "outer" ? oldElt : null,
    oldElt.nextSibling
  );
  ctxPantry.remove();
};
const morphChildren = (oldParent, newParent, insertionPoint = null, endPoint = null) => {
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
    if (newChild instanceof Element && ctxPersistentIds.has(newChild.id)) {
      const movedChild = document.getElementById(newChild.id);
      let current = movedChild;
      while (current = current.parentNode) {
        const idSet = ctxIdMap.get(current);
        if (idSet) {
          idSet.delete(newChild.id);
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
};
const findBestMatch = (node, startPoint, endPoint) => {
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
    cursor = cursor.nextSibling;
  }
  return bestMatch || null;
};
const isSoftMatch = (oldNode, newNode) => oldNode.nodeType === newNode.nodeType && oldNode.tagName === newNode.tagName && // If oldElt has an `id` with possible state and it doesn’t match newElt.id then avoid morphing.
// We'll still match an anonymous node with an IDed newElt, though, because if it got this far,
// its not persistent, and new nodes can't have any hidden state.
(!oldNode.id || oldNode.id === newNode.id);
const removeNode = (node) => {
  ctxIdMap.has(node) ? (
    // skip callbacks and move to pantry
    moveBefore(ctxPantry, node, null)
  ) : (
    // remove for realsies
    node.parentNode?.removeChild(node)
  );
};
const moveBefore = (
  // @ts-expect-error
  removeNode.call.bind(ctxPantry.moveBefore ?? ctxPantry.insertBefore)
);
const aliasedPreserveAttr = aliasify("preserve-attr");
const morphNode = (oldNode, newNode) => {
  const type = newNode.nodeType;
  if (type === 1) {
    const oldElt = oldNode;
    const newElt = newNode;
    if (oldElt.hasAttribute(aliasedIgnoreMorph) && newElt.hasAttribute(aliasedIgnoreMorph)) {
      return oldNode;
    }
    if (oldElt instanceof HTMLInputElement && newElt instanceof HTMLInputElement && newElt.type !== "file") {
      if (newElt.getAttribute("value") !== oldElt.getAttribute("value")) {
        oldElt.value = newElt.getAttribute("value") ?? "";
      }
    } else if (oldElt instanceof HTMLTextAreaElement && newElt instanceof HTMLTextAreaElement) {
      if (newElt.value !== oldElt.value) {
        oldElt.value = newElt.value;
      }
      if (oldElt.firstChild && oldElt.firstChild.nodeValue !== newElt.value) {
        oldElt.firstChild.nodeValue = newElt.value;
      }
    }
    const preserveAttrs = (newNode.getAttribute(aliasedPreserveAttr) ?? "").split(" ");
    for (const { name, value } of newElt.attributes) {
      if (oldElt.getAttribute(name) !== value && !preserveAttrs.includes(name)) {
        oldElt.setAttribute(name, value);
      }
    }
    for (let i = oldElt.attributes.length - 1; i >= 0; i--) {
      const { name } = oldElt.attributes[i];
      if (!newElt.hasAttribute(name) && !preserveAttrs.includes(name)) {
        oldElt.removeAttribute(name);
      }
    }
    if (!oldElt.isEqualNode(newElt)) {
      morphChildren(oldElt, newElt);
    }
  }
  if (type === 8 || type === 3) {
    if (oldNode.nodeValue !== newNode.nodeValue) {
      oldNode.nodeValue = newNode.nodeValue;
    }
  }
  return oldNode;
};
const populateIdMapWithTree = (root2, elements) => {
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
};
watcher({
  name: "datastar-patch-elements",
  apply(ctx, { elements = "", selector = "", mode = "outer", useViewTransition }) {
    switch (mode) {
      case "remove":
      case "outer":
      case "inner":
      case "replace":
      case "prepend":
      case "append":
      case "before":
      case "after":
        break;
      default:
        throw ctx.error("PatchElementsInvalidMode", { mode });
    }
    if (!selector && mode !== "outer" && mode !== "replace") {
      throw ctx.error("PatchElementsExpectedSelector");
    }
    const args2 = {
      mode,
      selector,
      elements,
      useViewTransition: useViewTransition?.trim() === "true"
    };
    if (supportsViewTransitions && useViewTransition) {
      document.startViewTransition(() => onPatchElements(ctx, args2));
    } else {
      onPatchElements(ctx, args2);
    }
  }
});
const onPatchElements = ({ error: error2 }, { elements, selector, mode }) => {
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
  if (!selector && (mode === "outer" || mode === "replace")) {
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
          console.warn(error2("PatchElementsNoTargetsFound"), {
            element: { id: child.id }
          });
          continue;
        }
      }
      applyToTargets(mode, child, [target]);
    }
  } else {
    const targets = document.querySelectorAll(selector);
    if (!targets.length) {
      console.warn(error2("PatchElementsNoTargetsFound"), { selector });
      return;
    }
    applyToTargets(mode, newContent, targets);
  }
};
const scripts = /* @__PURE__ */ new WeakSet();
for (const script of document.querySelectorAll("script")) {
  scripts.add(script);
}
const execute = (target) => {
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
};
const applyPatchMode = (targets, element, action2) => {
  for (const target of targets) {
    const cloned = element.cloneNode(true);
    execute(cloned);
    target[action2](cloned);
  }
};
const applyToTargets = (mode, element, targets) => {
  switch (mode) {
    case "remove":
      for (const target of targets) {
        target.remove();
      }
      break;
    case "outer":
    case "inner":
      for (const target of targets) {
        morph(target, element.cloneNode(true), mode);
        execute(target);
      }
      break;
    case "replace":
      applyPatchMode(targets, element, "replaceWith");
      break;
    case "prepend":
    case "append":
    case "before":
    case "after":
      applyPatchMode(targets, element, mode);
  }
};
watcher({
  name: "datastar-patch-signals",
  apply({ error: error2 }, { signals, onlyIfMissing }) {
    if (signals) {
      const ifMissing = onlyIfMissing?.trim() === "true";
      mergePatch(jsStrToObject(signals), { ifMissing });
    } else {
      throw error2("PatchSignalsExpectedSignals");
    }
  }
});
action({
  name: "dispatch",
  apply: ({ el }, eventName, data, options) => {
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
});
action({
  name: "fileUrl",
  apply: (_ctx, fileSource, options) => {
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
      const firstItem = fileSource[0];
      if (typeof firstItem === "object" && firstItem !== null && "contents" in firstItem) {
        const { contents, mime } = firstItem;
        if (!contents || typeof contents !== "string") {
          return fallback;
        }
        const mimeType2 = mime || defaultMime;
        return `data:${mimeType2};base64,${contents}`;
      }
      const base64Content = firstItem;
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
});
action({
  name: "back",
  apply: async (ctx, fallback = "/", key = "back", options = {}) => {
    let backUrl = fallback;
    if (document.referrer && document.referrer !== window.location.href) {
      try {
        const referrerUrl = new URL(document.referrer);
        const currentUrl = new URL(window.location.href);
        if (referrerUrl.origin === currentUrl.origin) {
          backUrl = document.referrer;
        }
      } catch (error2) {
        console.warn("Error parsing referrer URL:", error2);
      }
    }
    const navigateAction = actions.navigate;
    if (navigateAction) {
      await navigateAction(ctx, backUrl, key, {
        merge: true,
        // Default to merge for back navigation
        ...options
      });
    } else {
      console.error("Navigate action not found. Falling back to location.href");
      window.location.href = backUrl;
    }
  }
});
action({
  name: "refresh",
  apply: async (ctx, key = "refresh", options = {}) => {
    const currentUrl = window.location.href;
    const navigateAction = actions.navigate;
    if (navigateAction) {
      await navigateAction(ctx, currentUrl, key, {
        merge: true,
        // Default to merge for refresh
        replace: true,
        // Replace history entry (don't add new)
        ...options
      });
    } else {
      console.error("Navigate action not found. Falling back to location.reload");
      window.location.reload();
    }
  }
});
action({
  name: "reload",
  apply: (_ctx) => {
    window.location.reload();
  }
});
attribute({
  name: "error",
  requirement: {
    key: "denied",
    value: "must"
  },
  apply({ el, value }) {
    const fieldName = value.trim();
    mergePatch({ errors: {} }, { ifMissing: true });
    const errorComputed = computed(() => {
      const errors = getPath("errors");
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
    const cleanup2 = effect(() => {
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
});
const iterationStates = /* @__PURE__ */ new WeakMap();
const peek = (fn) => {
  startPeeking();
  try {
    fn();
  } finally {
    stopPeeking();
  }
};
attribute({
  name: "for",
  requirement: {
    key: "denied",
    value: "must"
  },
  apply(ctx) {
    const { el, value, mods, error: error2 } = ctx;
    if (!(el instanceof HTMLTemplateElement)) {
      throw error2("ForMustBeOnTemplate", {
        message: "data-for must be used on <template> elements"
      });
    }
    const parsed = parseExpression(value);
    if (!parsed) {
      throw error2("InvalidForExpression", {
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
    const sourceData = getPath(sourceSignalPath);
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
    const effectCleanup = effect(() => {
      const sourceData2 = getPath(sourceSignalPath);
      if (Array.isArray(sourceData2)) {
        const reactiveArray = root[sourceSignalPath];
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
      peek(() => {
        beginBatch();
        try {
          diffAndUpdate(newArrayWithKeys, state, ctx);
        } finally {
          endBatch();
        }
      });
    });
    state.effectCleanup = effectCleanup;
    return () => {
      cleanup(state);
      iterationStates.delete(el);
    };
  }
});
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
            mergePatch({
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
    handleAdd(finalArray, state);
  } else if (changeType === "simple-remove") {
    handleRemove(prevKeys, newKeys, state, ctx, finalArray);
  } else {
    handleReorder(finalArray, state);
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
    const loopEl = createElement(item, state);
    state.lookup.set(item.key, loopEl);
    prevEl.after(loopEl.el);
    prevEl = loopEl.el;
    queueMicrotask(() => apply(loopEl.el));
  }
}
function handleRemove(prevKeys, newKeys, state, _ctx, data) {
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
        const currentData = root[state.iterationId]?.[sanitizedKey];
        if (currentData !== void 0) {
          mergePatch({
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
          const currentData = root[state.iterationId]?.[sanitizedKey];
          if (currentData !== void 0) {
            mergePatch({
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
      const loopEl = createElement(item, state);
      state.lookup.set(item.key, loopEl);
      prevEl.after(loopEl.el);
      prevEl = loopEl.el;
      queueMicrotask(() => apply(loopEl.el));
    }
  }
}
function createElement(itemData, state, _ctx) {
  const { templateContent, sourceSignalPath, iteratorNames, isNormalized, iterationId } = state;
  const clone = templateContent.cloneNode(true);
  const el = clone.firstElementChild;
  let signalPath = sourceSignalPath;
  let indexSignalPath = null;
  if (!isNormalized) {
    const sanitizedKey = sanitizeKey(itemData.key);
    signalPath = `${iterationId}.${sanitizedKey}`;
    indexSignalPath = `${iterationId}.${sanitizedKey}__index`;
    mergePatch({
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
attribute({
  name: "if",
  requirement: {
    key: "denied",
    value: "must"
  },
  returnsValue: true,
  apply({ el, rx, error: error2 }) {
    if (!(el instanceof HTMLTemplateElement)) {
      throw error2("IfMustBeOnTemplate", {
        message: "data-if must be used on <template> elements"
      });
    }
    const templateContent = el.content;
    const rootElements = Array.from(templateContent.children);
    if (rootElements.length !== 1) {
      throw error2("IfTemplateMustHaveSingleRoot", {
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
      const conditionComputed = computed(() => {
        return rx();
      });
      const effectCleanup = effect(() => {
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
    } catch (err) {
      const instanceState = ifStates.get(el);
      if (instanceState) {
        cleanupIfInstance(instanceState);
        ifStates.delete(el);
      }
      throw err;
    }
  }
});
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
attribute({
  name: "navigate",
  requirement: {
    key: "denied",
    value: "must"
  },
  apply(ctx) {
    const { el, value, mods, error: error2 } = ctx;
    const navigateConfig = parseNavigateModifiers(mods, value.trim());
    if (!navigateConfig) {
      throw error2("InvalidNavigateConfiguration", {
        value,
        modifiers: Array.from(mods.keys())
      });
    }
    const handleNavigationWithCtx = (url2, config) => {
      handleNavigation(ctx, url2, config);
    };
    const executeNavigation = navigateConfig.timing ? createTimingWrapper(handleNavigationWithCtx, navigateConfig.timing) : handleNavigationWithCtx;
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
});
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
function handleNavigation(ctx, url2, config) {
  try {
    const finalUrl = processUrlWithMergeConfig(url2, config);
    const navigateAction = actions.navigate;
    if (!navigateAction) {
      console.error(
        "Navigate action not available in Datastar actions registry. Falling back to standard navigation."
      );
      window.location.href = finalUrl;
      return;
    }
    const navigateOptions = {
      merge: config.merge,
      replace: config.replace
    };
    if (config.only) {
      navigateOptions.only = config.only;
    }
    if (config.except) {
      navigateOptions.except = config.except;
    }
    navigateAction(ctx, finalUrl, config.key, navigateOptions);
  } catch (error2) {
    console.error("Navigation failed:", error2);
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
  } catch (error2) {
    console.warn("Error processing URL merge:", error2);
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
  action({
    name,
    apply: async (ctx, url2, args = {}) => {
      const baseActionName = method.toLowerCase();
      const baseAction = actions[baseActionName];
      if (!baseAction) {
        throw ctx.error(`BaseActionNotFound`, {
          method,
          baseActionName,
          availableActions: Object.keys(actions)
        });
      }
      const enhancedArgs = {
        ...args,
        credentials: "include",
        // Required for session cookies
        headers: {
          ...args?.headers,
          ...getCSRFHeaders(args?.headers)
        }
      };
      return await baseAction(ctx, url2, enhancedArgs);
    }
  });
};
createHttpMethodWithCSRF("postx", "POST");
createHttpMethodWithCSRF("putx", "PUT");
createHttpMethodWithCSRF("patchx", "PATCH");
createHttpMethodWithCSRF("deletex", "DELETE");
action({
  name: "navigate",
  apply: async (ctx, urlOrQueries, key = "true", options = {}) => {
    if (!urlOrQueries) {
      throw ctx.error("NavigateUrlRequired", {
        received: String(urlOrQueries)
      });
    }
    if (typeof key !== "string") {
      throw ctx.error("NavigateKeyMustBeString", {
        received: String(key)
      });
    }
    try {
      if (typeof urlOrQueries === "string") {
        if (/^[^?]*\s/.test(urlOrQueries)) {
          console.error("URL contains unencoded spaces, likely invalid:", urlOrQueries);
          return;
        }
      }
      const finalUrl = processNavigationInput(urlOrQueries, options);
      try {
        new URL(finalUrl, window.location.origin);
      } catch (urlError) {
        console.error("Invalid URL for navigation:", finalUrl, urlError);
        return;
      }
      const getAction = actions.get || actions.GET;
      if (!getAction) {
        console.error(
          "GET action not available in Datastar actions registry. Navigation falling back to full page load."
        );
        window.location.href = finalUrl;
        return;
      }
      const fetchArgs = {
        headers: {
          "HYPER-NAVIGATE": "true",
          "HYPER-NAVIGATE-KEY": key
        }
      };
      await getAction(ctx, finalUrl, fetchArgs);
      if (options.replace) {
        history.replaceState(null, "", finalUrl);
      } else {
        history.pushState(null, "", finalUrl);
      }
    } catch (error2) {
      console.error("Navigate action failed:", error2);
      try {
        const fallbackUrl = typeof urlOrQueries === "string" ? urlOrQueries : `${window.location.pathname}?${buildQueryString(
          urlOrQueries
        )}`;
        new URL(fallbackUrl, window.location.origin);
        window.location.href = fallbackUrl;
      } catch (urlError) {
        console.error("Invalid fallback URL, staying on current page:", urlError);
      }
    }
  }
});
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
  const queryString = buildQueryString(queries);
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
  } catch (error2) {
    console.warn("Error merging queries into URL:", error2);
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
  } catch (error2) {
    console.warn("Error merging query parameters:", error2);
    return url2;
  }
}
let interceptorSetup = false;
if (!interceptorSetup) {
  interceptorSetup = true;
  setupResponseInterceptor();
}
function setupResponseInterceptor() {
  const originalFetch = window.fetch;
  window.fetch = async (...args) => {
    const [resource, init] = args;
    const requestUrl = getUrlFromResource(resource);
    if (shouldSkipUrl(requestUrl)) {
      return originalFetch(...args);
    }
    const enhancedInit = {
      ...init,
      credentials: init?.credentials || "include"
    };
    const isDatastarRequest = enhancedInit?.headers?.["Datastar-Request"] === "true" || enhancedInit?.headers?.get?.("Datastar-Request") === "true";
    try {
      const response = await originalFetch(resource, enhancedInit);
      if (isRedirectResponse(response, requestUrl)) {
        const redirectUrl = getRedirectUrl(response);
        if (redirectUrl && redirectUrl !== window.location.href) {
          setTimeout(() => {
            window.location.href = redirectUrl;
          }, 300);
          throw new RedirectHandled();
        }
      }
      if (shouldPassThroughToDatastar(response, isDatastarRequest)) {
        return response;
      }
      if (shouldHandleLaravelResponse(response, isDatastarRequest)) {
        await handleLaravelResponse(response);
        return new Response("", { status: 200 });
      }
      return response;
    } catch (error2) {
      if (error2 instanceof RedirectHandled) {
        return new Response("", {
          status: 200,
          headers: {
            "Content-Type": "text/event-stream"
          }
        });
      }
      console.error("Network error:", error2);
      throw error2;
    }
  };
}
class RedirectHandled extends Error {
  constructor() {
    super("Redirect handled");
    this.name = "RedirectHandled";
  }
}
function shouldPassThroughToDatastar(response, isDatastarRequest) {
  if (!isDatastarRequest) return false;
  const contentType = response.headers.get("Content-Type") || "";
  if (response.headers.get("X-Hyper-Response") === "true") {
    return true;
  }
  if (contentType.includes("text/event-stream")) {
    return true;
  }
  const hasStandardContentType = contentType.includes("text/html") || contentType.includes("application/json") || contentType.includes("text/javascript");
  if (hasStandardContentType && response.status >= 200 && response.status < 400) {
    return true;
  }
  return false;
}
function shouldHandleLaravelResponse(response, isDatastarRequest) {
  const contentType = response.headers.get("Content-Type") || "";
  if (contentType.includes("text/event-stream") || response.headers.get("X-Hyper-Response") === "true") {
    return false;
  }
  if (response.status >= 400 && contentType.includes("text/html")) {
    return true;
  }
  if (response.status >= 200 && response.status < 400 && contentType.includes("text/html") && !isDatastarRequest) {
    return true;
  }
  return false;
}
async function handleLaravelResponse(response) {
  if (response.status < 400) return;
  const contentType = response.headers.get("Content-Type") || "";
  if (!contentType.includes("text/html")) return;
  try {
    const html = await response.clone().text();
    if (isLaravelSpecialResponse(html)) {
      replaceDocument(html);
    }
  } catch (error2) {
    console.error("Error reading response body:", error2);
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
  if (html.includes("<!DOCTYPE html>") && (html.includes("<title>404") || html.includes("<title>403") || html.includes("<title>500") || html.includes("<title>503") || html.includes("<title>Server Error") || html.includes("<title>Page Not Found") || html.includes("<title>Forbidden") || html.includes("<title>Service Unavailable"))) {
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
  } catch (error2) {
    console.error("Failed to replace document:", error2);
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
let globalNavigateSetup = false;
if (!globalNavigateSetup) {
  globalNavigateSetup = true;
  setupPopstateHandler();
  setupBackendNavigateHandler();
}
function setupPopstateHandler() {
  window.addEventListener("popstate", function(event) {
    if (!event.state) {
      window.location.reload();
      return;
    }
    const navigateAction = actions.navigate;
    if (navigateAction) {
      const navigateCtx = {
        el: document.body,
        error: (name, ctx) => {
          const err = new Error(`Navigation Error: ${name}`);
          console.error(err, ctx);
          return err;
        }
      };
      navigateAction(
        navigateCtx,
        window.location.href,
        "popstate"
      );
    } else {
      console.warn(
        "Navigate action not found. Falling back to page reload."
      );
      window.location.reload();
    }
  });
}
function setupBackendNavigateHandler() {
  document.addEventListener("hyper:navigate", function(event) {
    const { url: url2, key, options } = event.detail;
    const navigateAction = actions.navigate;
    if (navigateAction) {
      const navigateCtx = {
        el: document.body,
        error: (name, ctx) => {
          const err = new Error(`Navigation Error: ${name}`);
          console.error(err, ctx);
          return err;
        }
      };
      navigateAction(navigateCtx, url2, key || "true", options || {});
    } else {
      console.warn(
        "Navigate action not found. Falling back to window.location."
      );
      window.location.href = url2;
    }
  });
}
apply();
export {
  action,
  actions,
  apply,
  attribute,
  watcher
};
//# sourceMappingURL=hyper.js.map
