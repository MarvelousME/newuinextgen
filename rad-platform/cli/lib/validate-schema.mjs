/**
 * Lightweight JSON Schema subset validator (required, type, const, enum, pattern, additionalProperties).
 * Enough for RAD gate without npm dependencies.
 */

function typeOf(v) {
  if (v === null) return 'null';
  if (Array.isArray(v)) return 'array';
  return typeof v;
}

function matchType(schemaType, value) {
  const t = typeOf(value);
  if (Array.isArray(schemaType)) {
    return schemaType.some((s) => matchType(s, value));
  }
  if (schemaType === 'integer') return t === 'number' && Number.isInteger(value);
  if (schemaType === 'number') return t === 'number';
  return t === schemaType;
}

export function validateAgainstSchema(data, schema, path = '$') {
  const errors = [];

  if (schema.type && !matchType(schema.type, data)) {
    errors.push(`${path}: expected type ${JSON.stringify(schema.type)}, got ${typeOf(data)}`);
    return errors;
  }

  if (schema.const !== undefined && data !== schema.const) {
    errors.push(`${path}: expected const ${JSON.stringify(schema.const)}`);
  }

  if (schema.enum && !schema.enum.includes(data)) {
    errors.push(`${path}: value not in enum ${JSON.stringify(schema.enum)}`);
  }

  if (schema.pattern && typeof data === 'string') {
    const re = new RegExp(schema.pattern);
    if (!re.test(data)) errors.push(`${path}: does not match pattern ${schema.pattern}`);
  }

  if (schema.minLength !== undefined && typeof data === 'string' && data.length < schema.minLength) {
    errors.push(`${path}: minLength ${schema.minLength}`);
  }

  if (schema.type === 'object' && data && typeof data === 'object' && !Array.isArray(data)) {
    const props = schema.properties || {};
    for (const key of schema.required || []) {
      if (!(key in data)) errors.push(`${path}: missing required property "${key}"`);
    }
    for (const [key, value] of Object.entries(data)) {
      if (props[key]) {
        errors.push(...validateAgainstSchema(value, props[key], `${path}.${key}`));
      } else if (schema.additionalProperties === false) {
        errors.push(`${path}: additional property "${key}" not allowed`);
      }
    }
  }

  if (schema.type === 'array' && Array.isArray(data) && schema.items) {
    data.forEach((item, i) => {
      errors.push(...validateAgainstSchema(item, schema.items, `${path}[${i}]`));
    });
  }

  return errors;
}
