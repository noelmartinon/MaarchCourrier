import { Component, Input, OnInit, ElementRef, ViewChild } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../translate.component';
import { NotificationService } from '../notification.service';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';
import { AutoCompletePlugin } from '../../plugins/autocomplete.plugin';

declare function $j(selector: any): any;

@Component({
    selector: 'app-visa-workflow',
    templateUrl: 'visa-workflow.component.html',
    styleUrls: ['visa-workflow.component.scss'],
    providers: [NotificationService]
})
export class VisaWorkflowComponent extends AutoCompletePlugin implements OnInit {

    lang: any = LANG;
    visaWorkflow: any = {
        roles: ['sign', 'visa'],
        items: []
    };
    loading: boolean = false;
    data: any;

    @Input('injectDatas') injectDatas: any;

    constructor(public http: HttpClient, private notify: NotificationService) {
        super(http, ['signatureBookUsers']);
    }

    ngOnInit(): void { }

    drop(event: CdkDragDrop<string[]>) {
        if (event.previousContainer === event.container) {
            moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
        }
    }

    loadListModel(entityId: number) {
        this.loading = true;

        this.visaWorkflow.items = [];

        this.http.get("../../rest/listTemplates/entities/" + entityId)
            .subscribe((data: any) => {
                data.listTemplate.forEach((element: any) => {
                    if (element.object_type === 'VISA_CIRCUIT') {
                        element.requested_signature = (element.item_mode === 'visa' ? false : true);
                        this.visaWorkflow.items.push(element);
                    }
                });
                this.visaWorkflow.items.forEach((element: any) => {
                    if (element.externalId.maarchParapheur !== undefined) {
                        this.http.get("../../rest/maarchParapheur/user/" + element.externalId.maarchParapheur + "/picture")
                            .subscribe((data: any) => {
                                element.picture = data.picture;
                            }, (err: any) => {
                                this.notify.handleErrors(err);
                            });
                    }
                });
                this.loading = false;
            });
    }

    loadWorkflow(resId: number) {
        this.loading = true;
        this.visaWorkflow.items = [];
        this.http.get("../../rest/res/" + resId + "/visaCircuit")
            .subscribe((data: any) => {
                data.forEach((element: any) => {
                    this.visaWorkflow.items.push(element);
                });
                this.loading = false;
            }, (err: any) => {
                this.notify.handleErrors(err);
            });
    }

    loadWorkflowMaarchParapheur(attachment: any) {
        this.loading = true;
        this.visaWorkflow.items = [];
        const isVersion = attachment.relation > 1 ? '?isVersion=true':'';
        this.http.get(`../../rest/attachments/${attachment.res_id}/maarchParapheurWorkflow${isVersion}`)
            .subscribe((data: any) => {
                data.workflow.forEach((element: any) => {
                    if (element.processDate !== null) {
                        let arrDate = element.processDate.split('-');
                        arrDate = arrDate.concat(arrDate[arrDate.length-1].split(' '));
                        arrDate.splice(2,1);

                        const formatDate = `${arrDate[2]}-${arrDate[1]}-${arrDate[0]} ${arrDate[3]}`;

                        element.processDate = formatDate;

                    }
                    
                    
                    const user = {
                        'id': element.userId,
                        'labelToDisplay': element.userDisplay,
                        'requested_signature': element.mode === 'visa' ? false : true,
                        'process_date': element.processDate,
                        'picture': ''
                    }                    
                    this.visaWorkflow.items.push(user);
                    this.http.get("../../rest/maarchParapheur/user/" + element.userId + "/picture")
                        .subscribe((data: any) => {
                            this.visaWorkflow.items.filter((item: any) => item.id === element.userId)[0].picture = data.picture;
                        }, (err: any) => {
                            this.notify.handleErrors(err);
                        });
                });
                this.loading = false;
            }, (err: any) => {
                this.notify.handleErrors(err);
            });
    }

    deleteItem(index: number) {
        this.visaWorkflow.items.splice(index, 1);
    }

    getVisaCount() {
        return this.visaWorkflow.items.length;
    }

    changeRole(i: number) {
        this.visaWorkflow.items[i].requested_signature = !this.visaWorkflow.items[i].requested_signature;
    }

    getWorkflow() {
        return this.visaWorkflow.items;
    }

    checkExternalSignatoryBook() {
        let usersMissing: string[] = [];
        this.visaWorkflow.items.forEach((element: any) => {
            if (Object.keys(element.externalId).indexOf('maarchParapheur') === -1) {
                usersMissing.push(element.labelToDisplay);
            }
        });

        return usersMissing;
    }

    addItem(event: any) {
        const user = {
            'externalId': event.option.value.externalId,
            'labelToDisplay': event.option.value.idToDisplay,
            'requested_signature': false,
            'picture': ''
        }
        this.visaWorkflow.items.push(user);
        $j('#availableUsers').blur();
        this.userCtrl.setValue('');
        this.http.get("../../rest/maarchParapheur/user/" + user.externalId.maarchParapheur + "/picture")
            .subscribe((data: any) => {
                this.visaWorkflow.items[this.visaWorkflow.items.length - 1].picture = data.picture;
            }, (err: any) => {
                this.notify.handleErrors(err);
            });
    }
}