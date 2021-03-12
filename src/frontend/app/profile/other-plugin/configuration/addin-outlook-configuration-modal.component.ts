import { Component, OnInit } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { MatDialogRef } from '@angular/material/dialog';

@Component({
    templateUrl: 'addin-outlook-configuration-modal.component.html',
    styleUrls: ['addin-outlook-configuration-modal.component.scss'],
})
export class AddinOutlookConfigurationModalComponent implements OnInit {

    constructor(
        public translate: TranslateService,
        public dialogRef: MatDialogRef<AddinOutlookConfigurationModalComponent>,
    ) { }

    ngOnInit(): void { }
}
