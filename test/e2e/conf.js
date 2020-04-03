exports.config = {
    baseUrl: 'http://127.0.0.1/MaarchCourrier',
    seleniumAddress: 'http://localhost:4444/wd/hub',
    specs: [
        'index-resource-spec.js',
        //'login-spec.js',
        //'about-us-spec.js'
    ],
    capabilities: {
        browserName: 'chrome',
        chromeOptions: {
            args: ["--no-sandbox", "--headless", "--disable-gpu", "--window-size=800,600" ]
        },
    },
    chromeDriver: '/usr/bin/chromedriver'
};
