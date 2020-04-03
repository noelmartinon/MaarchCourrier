describe('angular homepage todo list', function() {
    it('should add a todo', function() {
        browser.waitForAngularEnabled(false);
        browser.get('http://127.0.0.1/maarch_trunk/apps/maarch_entreprise/index.php?display=true&page=login');

        element(by.id('login')).sendKeys('bbain');
        element(by.id('pass')).sendKeys('maarch');
        element(by.css('[name="submit"]')).click();

        // var todoList = element.all(by.repeater('todo in todoList.todos'));
        // expect(todoList.count()).toEqual(3);
        // expect(todoList.get(2).getText()).toEqual('write first protractor test');
        //
        // // You wrote your first test, cross it off the list
        // todoList.get(2).element(by.css('input')).click();
        // var completedAmount = element.all(by.css('.done-true'));
        // expect(completedAmount.count()).toEqual(2);
    });

    it('test 2', function() {
        browser.sleep(4000);
        browser.waitForAngularEnabled(false);
        var nbHeader = element.all(by.css('[class="bg-head-content"]'));
        expect(nbHeader.count()).toEqual(1);

        element(by.css('[routerLink="/about-us"]')).click();
    });
});

