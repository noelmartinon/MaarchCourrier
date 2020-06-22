import { Component, OnInit } from '@angular/core';

@Component({
    selector: 'app-welcome',
    templateUrl: './welcome.component.html',
    styleUrls: ['./welcome.component.scss']
})
export class WelcomeComponent implements OnInit {

    langs: string[] = [
        'fr',
        'en'
    ];

    constructor() { }

    ngOnInit(): void { }

    getInfoToInstall(): any[] {
        return [];
        /*return {
            body : {
                lang: this.selectedLang,
            },
            route : '/installer/lang'
        };*/
    }

}
