import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../app/translate.component';

@Injectable()
export class HeaderService {
    headerMessage: string = "";
    subHeaderMessage: string = "";
    user: any = { firstname: "", lastname: "" };
    menu: any[] = [];
    lang: any = LANG;

    constructor(public http: HttpClient) { }

    loadHeader() {
        this.http.get('../../rest/header')
            .subscribe((data: any) => {
                this.user = data.user;
                this.user.menu = data.menu;

                data.menu.unshift({
                    "name": this.lang.home,
                    "comment": this.lang.home,
                    "servicepage": "/home",
                    "shortcut": "true",
                    "style": "fa fa-home",
                    "angular": "true"
                });
                this.menu = data.menu;

            }, (err: any) => {
                console.log(err.error.errors);
            });
    }

    setHeader(maintTitle: string, subTitle: any = '') {
        this.headerMessage = maintTitle;
        this.subHeaderMessage = subTitle;
    }

    hasCurrentUserPrivilege(privilegeId: string) {
        return this.user.privileges.indexOf(privilegeId) > -1;
    }
}
