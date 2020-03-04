import { Component, OnInit, Input } from '@angular/core';
import { LANG } from '../../../translate.component';
import { NotificationService } from '../../../notification.service';
import { HttpClient } from '@angular/common/http';

declare function $j(selector: any): any;

@Component({
    selector: 'app-fast-paraph',
    templateUrl: "fast-paraph.component.html",
    styleUrls: ['fast-paraph.component.scss'],
})
export class FastParaphComponent implements OnInit {

    lang: any = LANG;
    loading: boolean = false;

    currentAccount: any = null;
    usersWorkflowList: any[] = [];

    injectDatasParam = {
        resId: 0,
        editable: true
    };
       
    @Input('additionalsInfos') additionalsInfos: any;
    @Input('externalSignatoryBookDatas') externalSignatoryBookDatas: any;

    constructor(public http: HttpClient, private notify: NotificationService) { }

    ngOnInit(): void {
    }

    isValidParaph() {
        if (this.additionalsInfos.attachments.length == 0) {
            return false;
        } else {
            return true;
        }
    }

    getRessources() {
        return this.additionalsInfos.attachments.map((e: any) => { return e.res_id; });
    }

    getDatas() {
        return this.externalSignatoryBookDatas;
    }
}
