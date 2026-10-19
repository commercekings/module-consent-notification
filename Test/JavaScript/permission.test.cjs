const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const {browser, load} = require('../../../module-consent/Test/JavaScript/harness.cjs');

/**
 * Notification's storefront permission (CommerceKings_Notification/js/permission) as the visitor's consent: this
 * module maps it to js/permission, which asks Consent's provider. Without a consent for the purpose, no sign-up.
 */
function permission(provider) {
    return load(browser(), 'module-consent-notification/view/frontend/web/js/permission.js',
        {'CommerceKings_Consent/js/consent/provider': provider});
}

/** A provider whose consent per purpose the test sets, announcing changes like Consent's does. */
function provider(initial = {}) {
    const states = {...initial}, listeners = {};
    return {
        hasConsent: purpose => states[purpose] === true,
        onChange: (purpose, callback) => (listeners[purpose] ||= []).push(callback),
        set(purpose, isOn) { states[purpose] = isOn; (listeners[purpose] || []).forEach(callback => callback(isOn)); }
    };
}

test('the module replaces Notification\'s permission (requirejs map)', () => {
    const file = path.join(__dirname, '../../view/frontend/requirejs-config.js');
    const context = vm.createContext({});
    vm.runInContext(fs.readFileSync(file, 'utf8') + ';this.result = config;', context);
    assert.equal(context.result.map['*']['CommerceKings_Notification/js/permission'],
        'CommerceKings_ConsentNotification/js/permission');
});

test('the permission for a purpose is the visitor\'s consent for it', () => {
    const consent = provider({cart_reminder: true});
    const subject = permission(consent);
    assert.equal(subject.hasPermission('cart_reminder'), true);
    assert.equal(subject.hasPermission('analytics'), false, 'no consent: no permission');
    assert.equal(subject.hasPermission('unknown_purpose'), false);
    consent.set('cart_reminder', false);
    assert.equal(subject.hasPermission('cart_reminder'), false, 'asked again each time, never cached');
});

test('changes of a purpose\'s consent reach its listeners, and only them', () => {
    const consent = provider();
    const subject = permission(consent);
    const reminder = [], analytics = [];
    subject.onChange('cart_reminder', isOn => reminder.push(isOn));
    subject.onChange('analytics', isOn => analytics.push(isOn));
    consent.set('cart_reminder', true);
    consent.set('cart_reminder', false);
    assert.deepEqual(reminder, [true, false]);
    assert.deepEqual(analytics, []);
});

test('over Consent\'s real provider with no banner configured: no permission for any purpose', () => {
    const b = browser();
    b.document.readyState = 'complete';
    b.document.getElementById = id => (id === 'commercekings-consent-config' ? {textContent: '{}'} : null);
    let adapter;
    load(b, 'module-consent/view/frontend/web/js/consent/adapter.js').load('', () => assert.fail('no adapter to load'),
        value => { adapter = value; }, {});
    const jquery = arg => { if (typeof arg === 'function') arg(); };
    jquery.mage = {cookies: {get: () => 'form-key', defaults: {path: '/', domain: ''}}};
    jquery.ajax = () => assert.fail('no request without a consent');
    b.context.BASE_URL = 'https://shop.example.test/';
    const consent = load(b, 'module-consent/view/frontend/web/js/consent/provider.js',
        {jquery, 'CommerceKings_Consent/js/consent/adapter!': adapter, 'mage/cookies': {}});
    const subject = load(b, 'module-consent-notification/view/frontend/web/js/permission.js',
        {'CommerceKings_Consent/js/consent/provider': consent});
    assert.equal(subject.hasPermission('cart_reminder'), false);
    assert.equal(subject.hasPermission('cart_reminder_sms'), false);
});
