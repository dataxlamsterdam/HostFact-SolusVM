# HostFact-SolusVM

## OpenVZ Version take the SolusVM directory
## KVM Version released 11-11-2020 take the SolusVMKVM directory
## XEN / Xen HVM is coming soon

HostFact module for SolusVM.
Voeg SolusVM als platform toe aan HostFact met deze module en automatiseer uw vps diensten.

Opmerkingen:
- Deze module is opensoure te gebruiken onder de MIT License voorwaarden. (onze auteursvermelding in code verplicht)
- De module is op dit moment ingesteld voor OpenVZ, eenvoudig om te bouwen voor KVM en/of Xen HVM stuur gerust een mail naar info@dataxl.nl bij vragen.

Installatie:
In de map 'Pro/3rdparty/modules/products/vps/integrations/' dient u de map 'SolusVM' te plaatsen waarna het platform SolusVM automatisch zichtbaar wordt in HostFact.

Mapstructuur:
De structuur in de map 'Pro/3rdparty/modules/products/vps/integrations/' zou nu als volgt moeten zijn:
- /SolusVM/SolusVM.php
- /SolusVM/version.php

Instellingen:
Nadat de 'installatie' stap is uitgevoerd dient u in te loggen op de 'Pro (beheersomgeving)' van HostFact.
Ga naar Beheer -> Diensten -> Servers en voeg een nieuwe server, slecteer als Platform 'SolusVM'.
Vergeet hierbij niet om de Node Group (ID) en Client username* in te vullen, anders werkt de koppeling niet correct.

* Gebruikersnaam van een Client account in SolusVM, maak het beste een Standaard Client account hiervoor aan in SolusVM

Custom VPS plan:
Het is mogelijk om een VPS aan te maken met custom memory, cpu, dataverkeer en schrijfruimte.
Hiervoor dient er een Plan aangemaakt te worden in SolusVM welke als basis wordt gebruikt, de specificaties van de VPS overschrijven dit Plan bij het nieuw aanmaken van een VPS.
De naam van het plan moet 'Custom' zijn en is hoofdletter gevoelig, verdere instellingen zoals Network Speed kunnen naar wens worden afgesteld.

Roadmap:
- De module voor Xen en Xen HVM beschikbaar stellen in deze reposority
- Uitbreiding maken dat er meerdere IP adressen aan een VPS kunnen worden toegewezen**
- Wijzigen van VPS specificaties op moment dat deze al bestaat**

** Voor deze functies in de roadmap moeten we wachten op een update van HostFact, op dit moment technisch niet mogelijk.
