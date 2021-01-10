/* collection of various utility functions used in the economics simulation */

function formatDollarAmount(amount){
    //const is_amount_negative = amount < 0;
    //return (is_amount_negative ? '-' : '') + '$' + ((is_amount_negative ? -1 : 1) * amount).toLocaleString();
    return (amount < 0 ? '-' : '') + '$' + Math.abs(amount).toLocaleString();
}

function safeArrAcc(arr, idx, defaultVal=null){
    // returns arr[idx] if the idx is an index within the bounds of arr, or default otherwise
    // will attempt to resolve a negative index `idx` to `arr.length + idx`
    // safe in the sense that it will not error given an index out of bounds, but return the default value instead
    return arr.length > idx && idx >= -arr.length
        ? idx > 0
            ? arr[idx]
            : arr[arr.length + idx]
        : defaultVal;
}

function safeObjAcc(obj, key, defaultVal=null){
    return safeObjNestAcc(obj, [key], defaultVal);
}

function safeObjNestAcc(obj, keys, defaultVal=null){
    return safeObjNestAccRecur(obj, keys, 0, defaultVal);
}

function safeObjNestAccRecur(obj, keys, keyIdx, defaultVal=null){
    return typeof obj == "undefined"
        ? defaultVal
        : keyIdx == keys.length
            ? obj
            // if we have an array with an unreachable index, try adding it to the length (may succeed for negative indices)
            : typeof obj[keys[keyIdx]] == "undefined" && typeof obj.length != "undefined" // && keys[keyIdx] < 0
                ? safeObjNestAccRecur(obj[obj.length + keys[keyIdx]], keys, keyIdx + 1, defaultVal)
                : safeObjNestAccRecur(obj[keys[keyIdx]], keys, keyIdx + 1, defaultVal);
}

/*
function cumulativeSumsArr(arr){
    return arr.reduce((curr, nxt) => curr.concat(safeArrAcc(curr, -1, 0) + nxt), []);
}
*/

const sumArr = arr => arr.reduce( (curr, nxt) => curr + nxt, 0 );

const cumulativeSumsArr = arr => arr.reduce( (curr, nxt) => curr.concat(safeArrAcc(curr, -1, 0) + nxt), []);

// const zip           = arrs => arrs[0].map(
//                             (_, i) => arrs.map(arr => arr[i]));

const zip           = (...arrs) => zip2DArr(arrs);

const zip2DArr      = arrs => arrs[0].map(
                            (_, i) => arrs.map(arr => arr[i]));

// const zipMin        = (...arrs) => rangeN( min(arrs.map(arr => arr.length)) ).map(
//                                                                     i => arrs.map(arr => arr[i]));

const zipMin        = (...arrs) => zipMin2DArr(arrs);

const zipMin2DArr   = arrs => rangeN( min(arrs.map(arr => arr.length)) ).map(
                                                                    i => arrs.map(arr => arr[i]));

// might want reduce so I can skip undefined values (off end of some arrays)
// reduce requires extra checks at each level: efficiency gain from reduce is not obvious
// const zipMax        = (...arrs) => rangeN( max(arrs.map(arr => arr.length)) ).map(
//                                                                     i => arrs.map(arr => arr[i])).filter( (ele) => (ele !== undefined) );

const zipMax        = (...arrs) => zipMax2DArr(arrs);

const zipMax2DArr   = arrs => rangeN( max(arrs.map(arr => arr.length)) ).map(
                                                                    i => arrs.map(arr => arr[i]).filter( ele => ele !== undefined ));

const maxF = (f, arr) =>
                arr.reduce( (curr, nxt) => curr == null || f(nxt) > f(curr) ? nxt : curr, null );

const max = arr => maxF(x => x, arr);

const min = arr => maxF(x => -x, arr);

// the fill method with no argument fills the array with undefined values
const range = (start, end, step=1) =>
                Array((end - start)/step).fill().map( (_, i) => i * step + start );

const rangeN = n => range(0, n, 1);

const objMap = (shouldMapKeys, shouldMapValues, f, obj) =>
                            Object.fromEntries(Object.entries(obj).map( ([k, v]) => [shouldMapKeys ? f(k) : k, shouldMapValues ? f(v) : v] ));

const objMapValues = (f, obj) => objMap(false, true, f, obj);
                        // Object.fromEntries(
                        //                 Object.keys(obj).map(k => [k, f(obj[k])]));

const objMapKeys = (f, obj) => objMap(true, false, f, obj);

const avgArr = arr => arr.length ? sumArr(arr)/arr.length : 0;

// probably not best performance but very elegant
const dropWhile = (pred, arr) => (arr.length && pred(first(arr)) ? dropWhile(pred, rest(arr)) : arr);

const dropWhileIdx = (pred, arr, idx=0) => (arr.length && pred(arr[idx]) ? dropWhile(pred, arr, idx+1) : arr);

const snakeToCamel = str => dropWhile(s => !s.length, str.split("_")).reduce((curr, nxt) => nxt.length
                                                                                            ? curr.concat(first(nxt).toLocaleUpperCase().concat(rest(nxt)))
                                                                                            : curr );

// const snakeToCamelX = str => dropWhile(s => !s.length, str.split("_")).reduce((curr, nxt) => nxt.length
//                                                                                             ? curr.concat(first(nxt).toLocaleUpperCase().concat(rest(nxt)))
//                                                                                             : curr );

const first = iterable => iterable[0];

const rest = iterable => iterable.slice(1);

// The concat method, when given an array, returns an array with adds each element of the array argument added to the original array
// This is problematic when you want to add an array directly as an element onto another array
// To work around this, wrap the array you'd like to add as an element in another array before passing as an arg to concat
const arrAdd = (arr, ele) => arr.concat(Array.isArray(ele) ? [ele] : ele);

const objUpdate = (obj, key, val) => ({...obj,
                                       [key]: val})

const mapIterToObj = (keyF, valF, iterable) => iterable.reduce( (curr, nxt) => objUpdate(curr, keyF(nxt), valF(nxt)), {});

const interpolate = (from, to, decimal) => (from * (1 - decimal) + to * decimal);

const getInterpolationDecimal = (from, to, val) => (val - from)/(to - from);

const interpolateColors = (from, to, decimal) => ( rangeN(3).map( idx => interpolate(from[idx], to[idx], decimal) ) );

const greenish = [42, 107, 6];// [0, 255, 55]

const reddish = [179, 20, 20];//[240, 54, 12]
