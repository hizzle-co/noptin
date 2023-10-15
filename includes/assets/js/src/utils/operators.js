export const operators = {
    '==': (a, b) => a == b,
    '===': (a, b) => a === b,
    '!=': (a, b) => a != b,
    '!==': (a, b) => a !== b,
    '>': (a, b) => a > b,
    '>=': (a, b) => a >= b,
    '<': (a, b) => a < b,
    '<=': (a, b) => a <= b,
    'includes': (a, b) => a.includes(b),
    '!includes': (a, b) => ! a.includes(b),
    'empty': (a) => ! a,
    '!empty': (a) => a,
};

export const compare = (a, operator, b) => operators[operator] ? operators[operator](a, b) : false;
