import { Component, Inject, OnInit, ViewChild } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { FunctionsService } from '@service/functions.service';

@Component({
    templateUrl: 'check-mail-server-modal.component.html',
    styleUrls: ['check-mail-server-modal.component.scss'],
})
export class CheckMailServerModalComponent implements OnInit{

    loading: boolean = false;

    constructor(
        @Inject(MAT_DIALOG_DATA) public data: any,
        public dialogRef: MatDialogRef<CheckMailServerModalComponent>,
        private functionsService: FunctionsService) {
    }

    ngOnInit(): void { }
}
