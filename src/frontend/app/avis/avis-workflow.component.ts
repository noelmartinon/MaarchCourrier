import { Component, Input, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../translate.component';
import { NotificationService } from '../notification.service';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';

@Component({
    selector: 'app-avis-workflow',
    templateUrl: 'avis-workflow.component.html',
    styleUrls: ['avis-workflow.component.scss'],
    providers: [NotificationService]
})
export class AvisWorkflowComponent implements OnInit {

    lang: any = LANG;
    avisWorkflow: any = {
        items : []
    };
    loading: boolean = true;
    data: any;

    @Input('injectDatas') injectDatas: any;

    constructor(public http: HttpClient, private notify: NotificationService) { }

    ngOnInit(): void { }

    drop(event: CdkDragDrop<string[]>) {
        if (event.previousContainer === event.container) {
            moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
        }
    }

    loadListModel(entityId: string) {
        this.loading = true;

        this.avisWorkflow.items = [];


        // TO DO : ADD ROUTE
        /*this.http.get("../../rest/???")
            .subscribe((data: any) => {
                this.loading = false;
            });*/

            this.avisWorkflow.items.push(
            {
                "listinstance_id": 20,
                "sequence": 0,
                "item_mode": "avis",
                "item_id": "bbain",
                "item_type": "user_id",
                "item_firstname": "Barbara",
                "item_lastname": "BAIN",
                "item_entity": "P\u00f4le Jeunesse et Sport",
                "viewed": 0,
                "process_date": null,
                "process_comment": "",
                "signatory": false,
                "requested_signature": false
            });

            this.avisWorkflow.items.push(
            {
                "listinstance_id": 21,
                "sequence": 0,
                "item_mode": "avis",
                "item_id": "DSG",
                "item_type": "entity_id",
                "item_entity": "Secr\u00e9tariat G\u00e9n\u00e9ral",
                "viewed": 0,
                "process_date": null,
                "process_comment": null,
                "signatory": false,
                "requested_signature": false
            }
        );

        this.loading = false;
    }

    loadWorkflow(resId: number) {
        this.loading = true;
        this.avisWorkflow.items = [];
        this.http.get("../../rest/res/" + resId + "/avisCircuit")
         .subscribe((data: any) => {
            data.forEach((element:any) => {
                this.avisWorkflow.items.push(element);
            });
            this.loading = false;
        }, (err: any) => {
            this.notify.handleErrors(err);
        });
    }

    deleteItem(index: number) {
        this.avisWorkflow.items.splice(index, 1);
    }

    getAvisCount() {
        return this.avisWorkflow.items.length;
    }
}