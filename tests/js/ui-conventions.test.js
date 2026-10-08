import assert from 'node:assert/strict';
import test from 'node:test';

import { checkSource } from '../../scripts/check-ui-conventions.mjs';

const rules = (source, file = 'resources/views/admin/example.blade.php') => checkSource(file, source).map((problem) => problem.rule);

test('semantic tokens pass', () => {
    assert.deepEqual(rules('<div class="bg-surface text-muted border-border hover:bg-surface-hover"></div>'), []);
});

test('Tailwind palette colors are rejected', () => {
    assert.deepEqual(rules('<div class="bg-amber-950 text-stone-500"></div>'), ['palette-class']);
    assert.deepEqual(rules('<div class="md:hover:text-red-600"></div>'), ['palette-class']);
});

test('white and black classes are rejected', () => {
    assert.deepEqual(rules('<div class="bg-white text-black"></div>'), ['black-white-class']);
});

test('color literals and arbitrary colors are rejected', () => {
    assert.ok(rules('.x { color: #451a03; }', 'resources/css/base.css').includes('color-literal'));
    assert.ok(rules('<div class="bg-[#451a03]"></div>').includes('arbitrary-color'));
});

test('inline style colors are rejected', () => {
    assert.deepEqual(rules('<div style="color: red"></div>'), ['inline-style-color']);
});

test('colors built from variables are rejected', () => {
    assert.deepEqual(rules('<div class="bg-{{ $tone }}"></div>'), ['dynamic-color-class']);
});

test('Alpine.start() is only allowed in the bootstrap file', () => {
    assert.deepEqual(rules('Alpine.start();', 'resources/js/ui/other.js'), ['alpine-start']);
    assert.deepEqual(rules('Alpine.start();', 'resources/js/app.js'), []);
});
