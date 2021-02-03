import { Component, Inject, ViewChild } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { TranslateService } from '@ngx-translate/core';
import { HttpClient } from '@angular/common/http';
import { ExternalVisaWorkflow } from '../externalVisaWorkflow/external-visa-workflow.component';


@Component({
    templateUrl: 'visa-workflow-modal.component.html',
    styleUrls: ['visa-workflow-modal.component.scss'],
})
export class VisaWorkflowModalComponent {
    

    @ViewChild('appExternalVisaWorkflow', { static: true }) appExternalVisaWorkflow: ExternalVisaWorkflow;

    constructor(public translate: TranslateService, public http: HttpClient, @Inject(MAT_DIALOG_DATA) public data: any, public dialogRef: MatDialogRef<VisaWorkflowModalComponent>) { }

    ngOnInit(): void {
        this.appExternalVisaWorkflow.loadWorkflowMaarchParapheur(this.data.id, this.data.type);
    }

}
