import { Component,  Inject,  OnInit, Renderer2 } from '@angular/core';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';

@Component({
    templateUrl: 'history-diffusions-list-modal.component.html',
    styleUrls: ['history-diffusions-list-modal.component.scss'],
})
export class HistoryDiffusionsListModalComponent implements OnInit {
    constructor(
        public dialog: MatDialog,
        @Inject(MAT_DIALOG_DATA) public data: any,
        public dialogRef: MatDialogRef<HistoryDiffusionsListModalComponent>,
    ) { }

    ngOnInit() { }
}
