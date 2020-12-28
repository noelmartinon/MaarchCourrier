import { Component,  Inject,  OnInit } from '@angular/core';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';

@Component({
    templateUrl: 'history-visa-workflow-modal.component.html',
    styleUrls: ['history-visa-workflow-modal.component.scss'],
})
export class HistoryVisaWorkflowModalComponent implements OnInit {
    constructor(
        public dialog: MatDialog,
        @Inject(MAT_DIALOG_DATA) public data: any,
        public dialogRef: MatDialogRef<HistoryVisaWorkflowModalComponent>,
    ) { }

    ngOnInit() { }
}
