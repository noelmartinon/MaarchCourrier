import { Injectable } from '@angular/core';
import { LANG } from '../app/translate.component';
import { JoyrideService } from 'ngx-joyride';
import { LocalStorageService } from './local-storage.service';
import { HeaderService } from './header.service';
import { FunctionsService } from './functions.service';
import { Router } from '@angular/router';

@Injectable({
    providedIn: 'root'
})
export class FeatureTourService {

    lang: any = LANG;

    currentStepType: string = '';

    currentTour: any = null;

    tour: any[] = [
        {
            type: 'email',
            stepId: 'admin_email_server',
            title: `<i class="far fa-question-circle" color="primary"></i>&nbsp;<b color="primary">${this.lang.admin_email_serverTitle}</b>`,
            description: this.lang.admin_email_serverTour,
            redirectToAdmin : false,
        },
        {
            type: 'email',
            stepId: 'emailTour@administration/sendmail',
            title: `<i class="far fa-question-circle" color="primary"></i>&nbsp;<b color="primary">${this.lang.emailTourTitle}</b>`,
            description: this.lang.emailTourDescription,
            redirectToAdmin : false,
        },
        {
            type: 'email',
            stepId: 'emailTour2@administration/sendmail',
            title: `<i class="far fa-question-circle" color="primary"></i>&nbsp;<b color="primary">${this.lang.emailTour2Title}</b>`,
            description: this.lang.emailTour2Description,
            redirectToAdmin : false,
        },
        {
            type: 'notification',
            stepId: 'admin_notif',
            title: `<i class="far fa-question-circle" color="primary"></i>&nbsp;<b color="primary">${this.lang.admin_notifTitle}</b>`,
            description: this.lang.admin_notifTour,
            redirectToAdmin : false,
        },
        {
            type: 'notification',
            stepId: 'notifTour@administration/notifications',
            title: `<i class="far fa-question-circle" color="primary"></i>&nbsp;<b color="primary">${this.lang.notifTourTitle}</b>`,
            description: this.lang.notifTourDescription,
            redirectToAdmin : false,
        },
        {
            type: 'notification',
            stepId: 'notifTour2@administration/notifications',
            title: `<i class="far fa-question-circle" color="primary"></i>&nbsp;<b color="primary">${this.lang.notifTour2Title}</b>`,
            description: this.lang.notifTour2Description,
            redirectToAdmin : false,
        },
        {
            type: 'notification',
            stepId: 'notifTour3@administration/notifications',
            title: `<i class="far fa-question-circle" color="primary"></i>&nbsp;<b color="primary">${this.lang.notifTour3Title}</b>`,
            description: this.lang.notifTour3Description,
            redirectToAdmin : false,
        },
        {
            type: 'notification',
            stepId: 'notifTour4@administration/notifications',
            title: `<i class="far fa-question-circle" color="primary"></i>&nbsp;<b color="primary">${this.lang.notifTour4Title}</b>`,
            description: this.lang.notifTour4Description,
            redirectToAdmin : true,
        },
    ];

    featureTourEnd: any[] = [];

    constructor(
        private readonly joyrideService: JoyrideService,
        private localStorage: LocalStorageService,
        private headerService: HeaderService,
        private functionService: FunctionsService,
        private router: Router
    ) {
        this.getCurrentStepType();
    }

    init() {
        if (!this.functionService.empty(this.currentStepType)) {
            setTimeout(() => {
                const steps = this.tour.filter(step => step.type === this.currentStepType).map(step => step.stepId);
                this.joyrideService.startTour(
                    {
                        customTexts: {
                            next: '>>',
                            prev: '<<',
                            done: this.lang.getIt
                        },
                        steps: steps
                    }
                ).subscribe(
                    step => {
                        /*Do something*/
                        this.currentTour = this.tour.filter((item: any) => item.stepId.split('@')[0] === step.name)[0];
                        const containerElement = document.getElementsByClassName('joyride-step__container') as HTMLCollectionOf<HTMLElement> 
                        containerElement[0].style.width = 'auto';
                        containerElement[0].style.height = 'auto';
                        document.getElementsByClassName('joyride-step__header')[0].innerHTML = `${this.currentTour.title}`;
                        document.getElementsByClassName('joyride-step__body')[0].innerHTML = `${this.currentTour.description}`;
                    },
                    error => {
                        /*handle error*/
                    },
                    () => {
                        if (this.currentTour.redirectToAdmin) {
                            this.router.navigate(['/administration']);
                        }
                        this.endTour();
                    }
                );
            }, 500);
        }
    }

    getCurrentStepType() {
        if (this.localStorage.get(`featureTourEnd_${this.headerService.user.id}`) !== null) {
            this.featureTourEnd = JSON.parse(this.localStorage.get(`featureTourEnd_${this.headerService.user.id}`));
        }
        const unique = [...new Set(this.tour.map(item => item.type))];

        this.currentStepType = unique.filter(stepType => this.featureTourEnd.indexOf(stepType) === -1)[0];
    }

    endTour() {
        this.featureTourEnd.push(this.currentStepType);
        this.localStorage.save(`featureTourEnd_${this.headerService.user.id}`, JSON.stringify(this.featureTourEnd));
        this.getCurrentStepType();
    }

}
