/**
 * Helper function to process complex nested values
 * @param {Object|string} valueMapping - The value mapping object or string
 * @param {Object} settings - The Elementor settings object
 * @returns {Object|*} - Processed values
 */
const processComplexValue = (valueMapping, settings) => {
  if (typeof valueMapping === 'string') {
    // For simple string mappings, get the value directly
    return settings[valueMapping]
  }

  const result = {}

  Object.entries(valueMapping).forEach(([key, mapping]) => {
    if (typeof mapping === 'string') {
      // Simple string mapping - get the value directly
      result[key] = settings[mapping]
    } else if (typeof mapping === 'object') {
      // Handle nested objects with value property
      if (mapping.value) {
        const value = settings[mapping.value]

        // Check if we need to extract size or return whole value
        if (mapping.return_size === false) {
          // When return_size is explicitly false, use the whole value
          if (value && typeof value === 'object' && value.size !== undefined && value.unit) {
            // Format with unit when available (like for scale)
            result[key] = `${value.size}${value.unit}`
          } else {
            result[key] = value
          }
        } else if (value && typeof value === 'object' && value.size !== undefined) {
          // Otherwise, return just the size value for objects with size property
          result[key] = value.size
        } else {
          // Fallback to whole value for simple types
          result[key] = value
        }
      } else {
        // Recursive processing for nested objects
        result[key] = processComplexValue(mapping, settings)
      }
    }
  })

  return result
}

/**
 * Converts Elementor settings to a format usable by JavaScript
 * @param {Object} settings - The Elementor settings object
 * @param {Object} settingsMap - Mapping of JS keys to Elementor keys
 * @returns {Object} - Converted settings object
 */
const convertSettings = (settings, settingsMap) => {
  const result = {}

  // Process each property in the map
  Object.entries(settingsMap).forEach(([jsKey, elementorMapping]) => {
    // Simple string mapping
    if (typeof elementorMapping === 'string') {
      if (settings[elementorMapping] !== undefined) {
        result[jsKey] = settings[elementorMapping]
      }
    }
    // Complex mapping with condition and value
    else if (typeof elementorMapping === 'object' && elementorMapping !== null) {
      // Check if this is a conditional mapping
      if (elementorMapping.condition) {
        // Set property to false if condition is not met
        if (!settings[elementorMapping.condition]) {
          result[jsKey] = false
          return
        }
      }

      // Handle simple value field
      if (typeof elementorMapping.value === 'string') {
        const value = settings[elementorMapping.value]

        // Extract size for values with "size" property when return_size is true
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
      }
      // Handle complex nested object in value
      else if (typeof elementorMapping.value === 'object' && elementorMapping.value !== null) {
        result[jsKey] = processComplexValue(elementorMapping.value, settings)
      }
      // Handle nested object mapping (no value/condition property)
      else if (!('value' in elementorMapping) && !('condition' in elementorMapping)) {
        // Recursively process nested mapping
        result[jsKey] = convertSettings(settings, elementorMapping)
      }
    }
  })

  return result
}

/**
 * Extracts settings keys from a settings map
 * @param {Object} settingsMap - The settings map object
 * @param {Array} additionalSettings - Additional settings to include
 * @returns {Array} - Array of setting keys
 */
const getLiveSettings = (settingsMap = {}, additionalSettings = []) => {
  const keys = []

  /**
   * Extracts keys from an object recursively
   * @param {Object|string} obj - The object to extract keys from
   */
  function extractFromObject(obj) {
    if (typeof obj === 'string') {
      keys.push(obj)
    } else if (typeof obj === 'object' && obj !== null) {
      // Extract condition keys
      if (obj.condition) {
        keys.push(obj.condition)
      }

      // Extract value keys
      if (obj.value) {
        if (typeof obj.value === 'string') {
          keys.push(obj.value)
        } else if (typeof obj.value === 'object') {
          extractFromObject(obj.value)
        }
      } else {
        // Process object keys if no value property
        Object.values(obj).forEach((val) => extractFromObject(val))
      }
    }
  }

  // Extract keys from the settings map
  Object.values(settingsMap).forEach((mapping) => extractFromObject(mapping))

  // Combine extracted keys with additional settings and remove duplicates
  return [...new Set([...keys, ...additionalSettings])]
}

export default {
  convertSettings,
  processComplexValue,
  getLiveSettings
}
