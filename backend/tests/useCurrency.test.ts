import assert from 'node:assert/strict';
import test from 'node:test';

import {
    selectedCurrency,
    useCurrency,
} from '../resources/js/composables/useCurrency.ts';

test('selected currency updates globally and formats converted values', () => {
    const { formatCurrency } = useCurrency();

    selectedCurrency.value = 'USD';
    assert.equal(selectedCurrency.value, 'USD');
    assert.equal(formatCurrency(16250), '$1.00');

    selectedCurrency.value = 'EUR';
    assert.equal(selectedCurrency.value, 'EUR');
    assert.equal(formatCurrency(17500), '€0.99');

    selectedCurrency.value = 'IDR';
    assert.equal(selectedCurrency.value, 'IDR');
    assert.equal(formatCurrency(1000000), 'IDR 1.0Jt');
});
