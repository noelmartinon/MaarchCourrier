var fs = require('fs');

// abstract writing screen shot to a file
function writeScreenShot(data, filename) {
    var stream = fs.createWriteStream(filename+'.png');
    stream.write(new Buffer.from(data, 'base64'));
    stream.end();
    // var stream = fs.createWriteStream(filename+'.txt');
    // stream.write(data);
    // stream.end();
    // console.log(data);
}

describe('index resource page', function () {
    it('index a document whitout file', function () {
        browser.sleep(2000);
        expect(browser.getCurrentUrl()).toEqual(browser.baseUrl + "/dist/index.html#/home");
        browser.takeScreenshot().then(function (png) {
            writeScreenShot(png, 'test/e2e/screenshots/home_' + browser.browserName);
        });
        element(by.id('indexing')).click();
        browser.sleep(500);
        browser.takeScreenshot().then(function (png) {
            writeScreenShot(png, 'test/e2e/screenshots/index_a_document_' + browser.browserName);
        });
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
