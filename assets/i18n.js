/**
 * Client-side i18n helper for Numa Log.
 *
 * The translation dictionary is injected inline before this script as
 *   window.I18N = { ...flat keyed strings... }; window.LANG = 'en'|'th';
 * (see config.php loadLang() / currentLang()).
 *
 *   t('common.total')                       → "Total"
 *   t('items.deleted', { n: 3 })            → "3 items deleted"   ({placeholders})
 *   tArr('date.months')[0]                  → "Jan"               (array entries)
 *
 * Missing keys fall back to the key itself so nothing renders blank.
 */
function t(key, params) {
    let s = (window.I18N && window.I18N[key] !== undefined) ? window.I18N[key] : key;
    if (typeof s !== 'string') return key;
    if (params) {
        for (const k in params) s = s.split('{' + k + '}').join(params[k]);
    }
    return s;
}

/** Fetch an array-valued entry (e.g. month / weekday name lists). */
function tArr(key) {
    const v = window.I18N && window.I18N[key];
    return Array.isArray(v) ? v : [];
}
