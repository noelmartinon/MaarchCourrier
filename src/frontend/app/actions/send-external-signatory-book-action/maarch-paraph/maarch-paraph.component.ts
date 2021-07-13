import { Component, OnInit, Input, ViewChild } from '@angular/core';
import { LANG } from '../../../translate.component';
import { NotificationService } from '../../../notification.service';
import { HttpClient } from '@angular/common/http';
import { VisaWorkflowComponent } from '../../../visa/visa-workflow.component';

declare function $j(selector: any): any;

@Component({
    selector: 'app-maarch-paraph',
    templateUrl: "maarch-paraph.component.html",
    styleUrls: ['maarch-paraph.component.scss'],
    providers: [NotificationService],
})
export class MaarchParaphComponent implements OnInit {

    lang: any = LANG;
    loading: boolean = false;

    currentAccount: any = null;
    usersWorkflowList: any[] = [];

    injectDatasParam = {
        resId: 0,
        editable: true
    };
   
    @ViewChild('appVisaWorkflow') appVisaWorkflow: VisaWorkflowComponent;
    
    @Input('additionalsInfos') additionalsInfos: any;
    @Input('externalSignatoryBookDatas') externalSignatoryBookDatas: any;

    constructor(public http: HttpClient, private notify: NotificationService) { }

    ngOnInit(): void {
        if (this.additionalsInfos.destinationId !== '') {
            this.appVisaWorkflow.loadListModel(this.additionalsInfos.destinationId);
        }
    }

    checkValidParaph() {
        if (this.additionalsInfos.attachments.length == 0 || this.appVisaWorkflow.getWorkflow().length === 0 || this.appVisaWorkflow.checkExternalSignatoryBook().length > 0) {
            return true;
        } else {
            return false;
        }
    }

    getRessources() {
        return this.additionalsInfos.attachments.map((e: any) => { return e.res_id; });
    }

    getDatas() {
        const workflow = this.appVisaWorkflow.getWorkflow();

        workflow.forEach((element:any) => {
            this.externalSignatoryBookDatas.steps.push(
                {
                    'externalId' : element.externalId.maarchParapheur,
                    'action' : element.requested_signature ? 'sign' : 'visa',
                }
            );
        });

        return this.externalSignatoryBookDatas;
    }
}
