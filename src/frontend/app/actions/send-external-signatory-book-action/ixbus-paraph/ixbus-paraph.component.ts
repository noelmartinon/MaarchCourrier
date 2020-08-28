import { Component, OnInit, Input } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '../../../../service/notification/notification.service';
import { HttpClient } from '@angular/common/http';
import { FormControl } from '@angular/forms';

@Component({
    selector: 'app-ixbus-paraph',
    templateUrl: 'ixbus-paraph.component.html',
    styleUrls: ['ixbus-paraph.component.scss'],
})
export class IxbusParaphComponent implements OnInit {

    
    loading: boolean = true;

    currentAccount: any = null;
    usersWorkflowList: any[] = [];
    natures: any[] = [];
    messagesModel: any[] = [];
    ixbusDatas: any = {
        nature: '',
        messageModel: '',
        login: '',
        password: '',
        signatureMode: 'manual'
    };

    injectDatasParam = {
        resId: 0,
        editable: true
    };

    selectNature = new FormControl();
    selectWorkflow = new FormControl();

    @Input() additionalsInfos: any;
    @Input() externalSignatoryBookDatas: any;

    constructor(public translate: TranslateService, public http: HttpClient, private notify: NotificationService) { }

    ngOnInit(): void {
        this.natures = this.additionalsInfos.ixbus.natures.map((element: any) => {
            return {
                id: element,
                label: element
            }
        });
        this.messagesModel = this.additionalsInfos.ixbus.messagesModel.map((element: any) => {
            return {
                id: element,
                label: element
            };
        });
        this.loading = false;
    }

    isValidParaph() {
        if (this.additionalsInfos.attachments.length === 0 || this.natures.length === 0 || this.messagesModel.length === 0 || !this.ixbusDatas.nature
            || !this.ixbusDatas.messageModel || !this.ixbusDatas.login || !this.ixbusDatas.password) {
            return false;
        } else {
            return true;
        }
    }

    getRessources() {
        return this.additionalsInfos.attachments.map((e: any) => e.res_id);
    }

    getDatas() {
        this.externalSignatoryBookDatas = {
            'ixbus': this.ixbusDatas,
            'steps': []
        };
        return this.externalSignatoryBookDatas;
    }
}
