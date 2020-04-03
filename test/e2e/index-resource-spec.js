describe('index resource page', function() {
    it('login to app', function() {
        browser.waitForAngularEnabled(false);
        browser.get(browser.baseUrl+ '/apps/maarch_entreprise/index.php?display=true&page=login');

        element(by.id('login')).sendKeys('bblier');
        element(by.id('pass')).sendKeys('maarch');
        element(by.css('[name="submit"]')).click();
    });

    it('index a document whitout file', function() {
        browser.sleep(5000);
        browser.waitForAngularEnabled(false);
        element(by.id('indexing')).click();
        browser.sleep(500);
        element(by.id('doctype')).click();
        browser.sleep(500);
        element(by.css('[title="Demande de renseignements"]')).click();
        browser.sleep(500);
        element(by.id('priority')).click();
        browser.sleep(500);
        element(by.css('[title="Normal"]')).click();
        browser.sleep(500);
        element(by.id('documentDate')).click();
        browser.sleep(500);
        element(by.css('.mat-calendar-body-active')).click();
        browser.sleep(500);
        element(by.id('subject')).sendKeys('test ee');
        browser.sleep(500);
        element(by.id('senders')).sendKeys('pascon');
        browser.sleep(1000);
        element(by.id('senders-6')).click();
        browser.sleep(500);
        element(by.id('destination')).click();
        browser.sleep(500);
        element(by.css('[title="Pôle Jeunesse et Sport"]')).click();
        browser.sleep(500);
        element(by.cssContainingText('.mat-button-wrapper', 'Valider')).click();
        browser.sleep(500);
        element(by.cssContainingText('.mat-button-wrapper', 'Ok')).click();
        browser.sleep(500);
        element(by.css('[placeholder="Ajouter une annotation"]')).sendKeys('test ee');
        browser.sleep(500);
        element(by.cssContainingText('.mat-dialog-content-container .mat-button-wrapper', 'Valider')).click();
        browser.sleep(500);
        expect(browser.getCurrentUrl()).toContain('/resources/');
        browser.sleep(4000);
    });
});

