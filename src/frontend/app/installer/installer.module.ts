import { NgModule } from '@angular/core';
import { SharedModule } from '../app-common.module';
import { InstallerComponent } from './installer.component';
import { InstallActionComponent } from './install-action/install-action.component';
import { WelcomeComponent } from './welcome/welcome.component';
import { PrerequisiteComponent } from './prerequisite/prerequisite.component';
import { DatabaseComponent } from './database/database.component';
import { DocserversComponent } from './docservers/docservers.component';
import { CustomizationComponent } from './customization/customization.component';
import { UseradminComponent } from './useradmin/useradmin.component';
import { InstallerRoutingModule } from './installer-routing.module';

@NgModule({
    imports: [
        SharedModule,
        InstallerRoutingModule
    ],
    declarations: [
        InstallActionComponent,
        InstallerComponent,
        WelcomeComponent,
        PrerequisiteComponent,
        DatabaseComponent,
        DocserversComponent,
        CustomizationComponent,
        UseradminComponent,
    ],
    entryComponents: [InstallActionComponent]
})
export class InstallerModule { }
