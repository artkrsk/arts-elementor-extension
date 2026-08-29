import type { TElementorSettings, TMappingValue, TSettingsMap, TValueMapping } from '@arts/utilities'

/** Slider-like dimension value probe ({ size, unit? }) */
const isDimensionLike = (value: unknown): value is { size: unknown; unit?: string } =>
  !!value && typeof value === 'object' && 'size' in value

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
 */
const processComplexValue = (
  valueMapping: TValueMapping,
  settings: TElementorSettings
): unknown => {
  if (typeof valueMapping === 'string') {
    return settings[valueMapping]
  }

  const result: Record<string, unknown> = {}

  Object.entries(valueMapping).forEach(([key, mapping]) => {
    if (typeof mapping === 'string') {
      result[key] = settings[mapping]
    } else if (typeof mapping === 'object') {
      const objectMapping = mapping as TMappingValue

      if (objectMapping.value && typeof objectMapping.value === 'string') {
        const value = settings[objectMapping.value]

        if (objectMapping.return_size === false) {
          if (isDimensionLike(value) && value.size !== undefined && value.unit) {
            // Concatenate size + unit (e.g. "1.5rem", "100%") so CSS-shaped
            // values can be forwarded verbatim by the consumer.
            result[key] = `${String(value.size)}${value.unit}`
          } else {
            result[key] = value
          }
        } else if (isDimensionLike(value) && value.size !== undefined) {
          result[key] = value.size
        } else {
          result[key] = value
        }
      } else {
        result[key] = processComplexValue(mapping as TValueMapping, settings)
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
 */
const convertSettings = (
  settings: TElementorSettings,
  settingsMap: TSettingsMap
): Record<string, unknown> => {
  const result: Record<string, unknown> = {}

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

        if (elementorMapping.return_size === true && isDimensionLike(value) && value.size !== undefined) {
          result[jsKey] = value.size
        } else {
          result[jsKey] = value
        }
      } else if (typeof elementorMapping.value === 'object' && elementorMapping.value !== null) {
        result[jsKey] = processComplexValue(elementorMapping.value as TValueMapping, settings)
      } else if (!('value' in elementorMapping) && !('condition' in elementorMapping)) {
        result[jsKey] = convertSettings(settings, elementorMapping as TSettingsMap)
      }
    }
  })

  return result
}

/**
 * Flattens a settings map into the de-duplicated list of Elementor setting keys
 * it depends on — used to subscribe to the right `addChangeCallback` controls.
 */
const getLiveSettings = (
  settingsMap: TSettingsMap = {},
  additionalSettings: string[] = []
): string[] => {
  const keys: string[] = []

  function extractFromObject(obj: unknown): void {
    if (typeof obj === 'string') {
      keys.push(obj)
    } else if (typeof obj === 'object' && obj !== null) {
      const mapping = obj as TMappingValue

      if (mapping.condition) {
        keys.push(mapping.condition)
      }

      if (mapping.value) {
        if (typeof mapping.value === 'string') {
          keys.push(mapping.value)
        } else if (typeof mapping.value === 'object') {
          extractFromObject(mapping.value)
        }
      } else {
        Object.values(mapping).forEach((val) => extractFromObject(val))
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
