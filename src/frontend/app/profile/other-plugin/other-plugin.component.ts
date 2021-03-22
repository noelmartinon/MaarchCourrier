import { Component, OnInit } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { HeaderService } from '@service/header.service';
import { AddinOutlookConfigurationModalComponent } from './configuration/addin-outlook-configuration-modal.component';
import { MatDialog } from '@angular/material/dialog';

@Component({
    selector: 'app-other-plugin',
    templateUrl: './other-plugin.component.html',
    styleUrls: ['./other-plugin.component.scss'],
})

export class ProfileOtherPluginComponent implements OnInit {


    constructor(
        public translate: TranslateService,
        public headerService: HeaderService,
        public dialog: MatDialog,
    ) {}

    ngOnInit(): void { }

    openAddinOutlookConfiguration() {
        this.dialog.open(AddinOutlookConfigurationModalComponent, {
            panelClass: 'maarch-modal',
            width: '99%',
        });
    }
}