import { Component, OnInit, ViewChild } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { HeaderService } from '../../service/header.service';
import { NotificationService } from '../notification.service';
import { STEPPER_GLOBAL_OPTIONS } from '@angular/cdk/stepper';
import { MatStepper } from '@angular/material/stepper';

@Component({
    templateUrl: './installer.component.html',
    styleUrls: ['./installer.component.scss'],
    providers: [{
        provide: STEPPER_GLOBAL_OPTIONS, useValue: { showError: true }
    }]
})
export class InstallerComponent implements OnInit {

    @ViewChild('stepper', { static: true }) stepper: MatStepper;

    constructor(
        private http: HttpClient,
        private router: Router,
        private headerService: HeaderService,
        private notify: NotificationService,
    ) { }

    ngOnInit(): void {
        this.headerService.hideSideBar = true;
    }

    isValidStep() {
        return false;
    }

    initStep(ev: any) {
        console.log(ev.selectedStep.content);
    }

    endInstall() {
        this.stepper.next();
    }

}
