import { Component, Inject, ViewChild } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material';
import { LANG } from '../../translate.component';
import { HttpClient } from '@angular/common/http';
import { VisaWorkflowComponent } from '../visa-workflow.component';


@Component({
    templateUrl: 'visa-workflow-modal.component.html',
    styleUrls: ['visa-workflow-modal.component.scss'],
})
export class VisaWorkflowModalComponent {
    lang: any = LANG;

    injectDatasParam = {
        resId: 0,
        editable: false
    };

    @ViewChild('appVisaWorkflow') appVisaWorkflow: VisaWorkflowComponent;

    constructor(public http: HttpClient, @Inject(MAT_DIALOG_DATA) public data: any, public dialogRef: MatDialogRef<VisaWorkflowModalComponent>) { }

    ngOnInit(): void {
        this.appVisaWorkflow.loadWorkflowMaarchParapheur(this.data.attachment.res_id);
    }

}
