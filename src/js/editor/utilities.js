/**
 * Recursively resolves a value-mapping against an Elementor settings object.
 *
 * Mapping shapes accepted:
 *   - string → `settings[mapping]`
 *   - { value: <string>, return_size?: boolean } → reads `settings[value]`; when the
 *     value is a Slider-like `{ size, unit? }` it is reduced according to
 *     `return_size` (false ⇒ `"<size><unit>"` when unit is present, else raw value;
 *     anything else ⇒ just the `size`).
 *   - any other object → processed recursively, keyed under each property.
 *
 * @param {Object|string} valueMapping
 * @param {Object} settings - The Elementor settings object
 * @returns {Object|*}
 */
const processComplexValue = (valueMapping, settings) => {
  if (typeof valueMapping === 'string') {
    return settings[valueMapping]
  }

  const result = {}

  Object.entries(valueMapping).forEach(([key, mapping]) => {
    if (typeof mapping === 'string') {
      result[key] = settings[mapping]
    } else if (typeof mapping === 'object') {
      if (mapping.value) {
        const value = settings[mapping.value]

        if (mapping.return_size === false) {
          if (value && typeof value === 'object' && value.size !== undefined && value.unit) {
            // Concatenate size + unit (e.g. "1.5rem", "100%") so CSS-shaped
            // values can be forwarded verbatim by the consumer.
            result[key] = `${value.size}${value.unit}`
          } else {
            result[key] = value
          }
        } else if (value && typeof value === 'object' && value.size !== undefined) {
          result[key] = value.size
        } else {
          result[key] = value
        }
      } else {
        result[key] = processComplexValue(mapping, settings)
      }
    }
  })

  return result
}

/**
 * Walks a `settingsMap` describing how to expose Elementor settings to JS, and
 * returns the resolved values keyed by the JS-side names.
 *
 * Mapping shapes per JS key:
 *   - string → direct lookup in settings (omitted when undefined).
 *   - { condition?: string, value: string|object, return_size?: boolean }
 *       • `condition` falsy in settings → result is `false`.
 *       • string `value` → lookup; with `return_size === true` and an object
 *         carrying `size`, only `size` is returned.
 *       • object `value` → delegated to processComplexValue().
 *   - bare nested object (no `value`/`condition`) → recursed as a sub-map.
 *
 * @param {Object} settings - The Elementor settings object
 * @param {Object} settingsMap - Mapping of JS keys to Elementor keys
 * @returns {Object}
 */
const convertSettings = (settings, settingsMap) => {
  const result = {}

  Object.entries(settingsMap).forEach(([jsKey, elementorMapping]) => {
    if (typeof elementorMapping === 'string') {
      if (settings[elementorMapping] !== undefined) {
        result[jsKey] = settings[elementorMapping]
      }
    } else if (typeof elementorMapping === 'object' && elementorMapping !== null) {
      if (elementorMapping.condition) {
        if (!settings[elementorMapping.condition]) {
          result[jsKey] = false
          return
        }
      }

      if (typeof elementorMapping.value === 'string') {
        const value = settings[elementorMapping.value]

        if (
          elementorMapping.return_size === true &&
          value &&
          typeof value === 'object' &&
          value.size !== undefined
        ) {
          result[jsKey] = value.size
        } else {
          result[jsKey] = value
        }
      } else if (typeof elementorMapping.value === 'object' && elementorMapping.value !== null) {
        result[jsKey] = processComplexValue(elementorMapping.value, settings)
      } else if (!('value' in elementorMapping) && !('condition' in elementorMapping)) {
        result[jsKey] = convertSettings(settings, elementorMapping)
      }
    }
  })

  return result
}

/**
 * Flattens a settings map into the de-duplicated list of Elementor setting keys
 * it depends on — used to subscribe to the right `addChangeCallback` controls.
 *
 * @param {Object} settingsMap
 * @param {Array} additionalSettings - Extra keys appended verbatim before de-duplication
 * @returns {Array<string>}
 */
const getLiveSettings = (settingsMap = {}, additionalSettings = []) => {
  const keys = []

  function extractFromObject(obj) {
    if (typeof obj === 'string') {
      keys.push(obj)
    } else if (typeof obj === 'object' && obj !== null) {
      if (obj.condition) {
        keys.push(obj.condition)
      }

      if (obj.value) {
        if (typeof obj.value === 'string') {
          keys.push(obj.value)
        } else if (typeof obj.value === 'object') {
          extractFromObject(obj.value)
        }
      } else {
        Object.values(obj).forEach((val) => extractFromObject(val))
      }
    }
  }

  Object.values(settingsMap).forEach((mapping) => extractFromObject(mapping))

  return [...new Set([...keys, ...additionalSettings])]
}

export default {
  convertSettings,
  processComplexValue,
  getLiveSettings
}
