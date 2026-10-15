<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Convenio de colaboración</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #1f2937; line-height: 1.5; }
        h1 { font-size: 15px; text-align: center; margin: 0 0 2px; }
        h2 { font-size: 13px; text-align: center; margin: 0 0 16px; }
        .empresa { text-align: center; font-size: 12px; font-weight: bold; margin-bottom: 4px; }
        p { margin: 0 0 10px; text-align: justify; }
        .clausula-titulo { font-weight: bold; margin: 14px 0 4px; }
        ul { margin: 4px 0 10px; padding-left: 18px; }
        li { margin-bottom: 4px; }
        .declaracion-titulo { font-weight: bold; text-align: center; margin: 18px 0 8px; }
        .firmas { margin-top: 40px; width: 100%; }
        .firmas table { width: 100%; border-collapse: collapse; }
        .firmas td { width: 50%; text-align: center; vertical-align: bottom; padding: 0 10px; }
        .firma-caja { height: 74px; text-align: center; }
        .firma-img { height: 68px; }
        .firma-linea { border-top: 1px solid #1f2937; margin-top: 0; padding-top: 4px; }
        .footer { margin-top: 24px; font-size: 8.5px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <p class="empresa">LUMEN STRATYX E.I.R.L.</p>
    <h1>CONVENIO DE COLABORACIÓN PROFESIONAL</h1>
    <h2>Y CONFIDENCIALIDAD</h2>

    <p>
        Conste por el presente documento el Convenio de Colaboración Profesional que celebran
        el {{ $generatedAt->translatedFormat('d \d\e F \d\e Y') }}, de una parte, LUMEN STRATYX E.I.R.L., identificada con
        RUC N.° 20615988902, debidamente representada por su Gerente General, Martín
        Alonso Andrés Puertas Cuadros, identificado con DNI N.° 70621234, a quien en adelante
        se le denominará "LA EMPRESA"; y de la otra parte, {{ $employee->fullName() }},
        identificado(a) con DNI N.° {{ $employee->documento_identidad ?? '___________________' }}, a quien en adelante se le
        denominará "EL COLABORADOR"; en los términos y condiciones siguientes:
    </p>

    <p>
        Este convenio constituye el marco general de colaboración entre LUMEN STRATYX
        E.I.R.L. y sus colaboradores independientes.
    </p>

    <p style="font-weight:bold;">CLÁUSULAS PRINCIPALES</p>

    <p class="clausula-titulo">1. PRIMERA: OBJETO</p>
    <p>
        Regular la participación del colaborador en proyectos tecnológicos, marketing digital,
        diseño UX/UI, branding, SEO, desarrollo web, software, ciberseguridad, auditorías y
        demás servicios ofrecidos por la empresa.
    </p>

    <p class="clausula-titulo">2. SEGUNDA: NATURALEZA DE LA RELACIÓN</p>
    <p>
        Las partes dejan expresa constancia de que el presente convenio es voluntario, y no
        genera vínculo laboral, subordinación laboral, relación de dependencia ni obligación de
        incorporación a planilla, constituyendo una relación de colaboración independiente
        orientada al desarrollo de proyectos específicos.
    </p>
    <p>
        EL COLABORADOR desarrollará sus actividades con autonomía técnica y profesional,
        en sus tiempos libres, sin un horario establecido por LA EMPRESA, comprometiéndose a
        cumplir los lineamientos, estándares de calidad, políticas internas y procedimientos
        establecidos por LA EMPRESA.
    </p>

    <p class="clausula-titulo">3. TERCERA: PLAZO</p>
    <p>Duración de seis (6) meses renovables por acuerdo escrito.</p>

    <p class="clausula-titulo">4. CUARTA: DESVINCULACIÓN ANTICIPADA</p>
    <p>
        EL COLABORADOR podrá solicitar su desvinculación antes del vencimiento del
        presente convenio mediante comunicación escrita dirigida al Gerente General de LA
        EMPRESA.
    </p>
    <p>
        La desvinculación procurará ser coordinada previamente con el Gerente General, a fin de
        garantizar la adecuada entrega de actividades, documentación, accesos, materiales y
        proyectos en los que EL COLABORADOR esté participando.
    </p>
    <p>
        Asimismo, LA EMPRESA podrá dar por concluido el presente convenio en cualquier
        momento cuando exista incumplimiento de obligaciones, faltas a la ética profesional,
        vulneración de la confidencialidad o afectación a los intereses de la organización.
    </p>

    <p class="clausula-titulo">5. QUINTA: REPRESENTACIÓN ANTE CLIENTES</p>
    <p>
        EL COLABORADOR reconoce que toda negociación, coordinación, contratación,
        cotización, propuesta económica, modificación contractual, ampliación de servicios,
        condiciones comerciales, acuerdos financieros o cualquier decisión administrativa
        relacionada con clientes corresponde exclusivamente a LA EMPRESA y será canalizada
        únicamente a través del Gerente General o de quien éste autorice expresamente por
        escrito.
    </p>
    <p>
        En consecuencia, EL COLABORADOR se obliga a no negociar precios, descuentos,
        contratos, condiciones económicas, formas de pago, ampliaciones de alcance ni acuerdos
        comerciales directamente con clientes.
    </p>
    <p>Toda comunicación relacionada con dichos aspectos deberá ser derivada inmediatamente al Gerente General.</p>

    <p class="clausula-titulo">6. SEXTA: CONFIDENCIALIDAD (NDA)</p>
    <p>EL COLABORADOR se compromete a mantener absoluta reserva y confidencialidad respecto de toda información relacionada con:</p>
    <ul>
        <li>Clientes.</li>
        <li>Estrategias comerciales.</li>
        <li>Propuestas económicas.</li>
        <li>Información financiera.</li>
        <li>Procesos internos.</li>
        <li>Bases de datos.</li>
        <li>Documentación técnica.</li>
        <li>Accesos, contraseñas y sistemas.</li>
        <li>Proyectos desarrollados por LA EMPRESA.</li>
    </ul>
    <p>La obligación de confidencialidad permanecerá vigente incluso después de concluido el presente convenio.</p>

    <p class="clausula-titulo">7. SÉPTIMA: PROPIEDAD INTELECTUAL Y PORTAFOLIO</p>
    <p>
        Todo diseño, desarrollo, código fuente, documento, estrategia, contenido, material
        gráfico, audiovisual, propuesta, informe o entregable elaborado durante la vigencia del
        presente convenio para clientes o proyectos de LA EMPRESA será de propiedad
        exclusiva de LA EMPRESA o del cliente correspondiente, según lo establecido en los
        contratos aplicables.
    </p>
    <p>
        Sin perjuicio de lo anterior, LA EMPRESA reconoce el derecho de EL
        COLABORADOR a incluir en su portafolio profesional aquellos proyectos en los que
        haya participado de manera directa y comprobable, aun cuando haya concluido su
        relación de colaboración con LA EMPRESA.
    </p>
    <p>
        Para tal efecto, EL COLABORADOR podrá mostrar capturas, descripciones generales,
        enlaces públicos, material visual autorizado o referencias del proyecto exclusivamente
        con fines académicos, curriculares, profesionales o de promoción de su experiencia
        laboral, siempre que:
    </p>
    <ul>
        <li>a) No divulgue información confidencial, estratégica o reservada del cliente o de LA EMPRESA.</li>
        <li>b) No revele código fuente, accesos, credenciales, documentación interna, metodologías privadas o información protegida.</li>
        <li>c) Indique de manera expresa que el proyecto fue desarrollado como parte de su participación en LUMEN STRATYX E.I.R.L.</li>
        <li>d) No atribuya a su autoría exclusiva trabajos realizados en colaboración con otros miembros del equipo.</li>
        <li>e) Respete las restricciones de confidencialidad que pudieran existir por acuerdos suscritos con clientes específicos.</li>
    </ul>
    <p>
        La presente autorización no implica transferencia de derechos de propiedad intelectual,
        licencias de explotación comercial ni facultades para reutilizar total o parcialmente los
        entregables en proyectos distintos sin autorización previa y escrita de LA EMPRESA o
        del cliente titular de los derechos correspondientes.
    </p>

    <p class="clausula-titulo">8. OCTAVA: DESARROLLO COMERCIAL, CAPTACIÓN DE CLIENTES E INCENTIVOS</p>
    <p>LA EMPRESA reconoce y promueve la participación activa de sus colaboradores en la generación de oportunidades comerciales y captación de nuevos clientes.</p>
    <p>
        Los colaboradores podrán identificar, referir o captar potenciales clientes para LUMEN
        STRATYX E.I.R.L, debiendo canalizar toda negociación, propuesta económica,
        contratación y formalización del servicio a través del Gerente General o de quien éste
        autorice expresamente.
    </p>
    <p>
        Cuando un cliente referido o captado por un colaborador se convierta en cliente efectivo
        de LA EMPRESA, dicho colaborador tendrá derecho a participar en los beneficios
        económicos establecidos por la política interna de distribución de ingresos e incentivos de
        la organización.
    </p>
    <p>
        Si el colaborador capta un cliente y no participa operativamente en la ejecución del
        proyecto, será igualmente considerado participante del proyecto para efectos de la
        distribución del porcentaje económico correspondiente que determine LA EMPRESA.
    </p>
    <p>
        Si el colaborador capta un cliente y además participa activamente en la ejecución del
        proyecto, tendrá derecho a recibir la compensación correspondiente a su participación
        operativa y, adicionalmente, una bonificación comercial especial proveniente de la
        utilidad empresarial generada por dicho proyecto, según los criterios establecidos por LA
        EMPRESA.
    </p>
    <p>
        En ningún caso la captación de clientes otorgará al colaborador derechos de propiedad
        sobre los mismos, manteniéndose la relación comercial exclusivamente entre el cliente y
        LUMEN STRATYX E.I.R.L.
    </p>
    <p>
        Los colaboradores se comprometen a no contratar directamente, desviar, retener o
        aprovechar para beneficio propio clientes obtenidos a través de LA EMPRESA sin
        autorización expresa y escrita del Gerente General.
    </p>

    <p class="clausula-titulo">9. NOVENA: USO DE ACTIVOS DIGITALES</p>
    <p>Todo correo corporativo, hosting, dominio, repositorio, nube, sistema, licencia o herramienta proporcionada por la empresa será utilizado exclusivamente para fines profesionales.</p>

    <p class="clausula-titulo">10. DÉCIMA: SEGURIDAD DE LA INFORMACIÓN</p>
    <p>El colaborador deberá proteger contraseñas, accesos, bases de datos y sistemas. Queda prohibido compartir credenciales con terceros.</p>

    <p class="clausula-titulo">11. DÉCIMA PRIMERA: CÓDIGO DE CONDUCTA</p>
    <p>EL COLABORADOR deberá actuar con profesionalismo, respeto, ética, responsabilidad y compromiso, evitando cualquier conducta que pueda afectar la reputación, imagen o intereses de LA EMPRESA.</p>

    <p class="clausula-titulo">12. DÉCIMA SEGUNDA: REMUNERACIÓN</p>
    <p>La empresa promoverá un modelo de colaboración basado en la participación y contribución efectiva de sus integrantes.</p>
    <p>Para cada proyecto ejecutado por LA EMPRESA, los ingresos obtenidos podrán distribuirse considerando los siguientes criterios:</p>
    <ul>
        <li>a) Un porcentaje destinado a utilidades y sostenibilidad empresarial.</li>
        <li>b) Un porcentaje destinado a cubrir gastos operativos, herramientas, licencias, servicios externos, imprevistos o cualquier necesidad relacionada con la correcta ejecución del proyecto.</li>
        <li>c) El monto restante será distribuido entre los participantes del proyecto conforme a los criterios internos definidos por LA EMPRESA.</li>
    </ul>
    <p>La empresa procurará aplicar principios de equidad, transparencia y reconocimiento al esfuerzo individual y colectivo de los colaboradores involucrados.</p>
    <p>Los porcentajes específicos podrán ser definidos mediante políticas internas, anexos de proyecto o acuerdos particulares según la naturaleza, complejidad y rentabilidad de cada servicio contratado.</p>

    <p class="clausula-titulo">13. DÉCIMA TERCERA: ANEXOS DE PROYECTO</p>
    <p>Cada proyecto podrá contar con un anexo indicando cliente, alcance, funciones, plazo, entregables y compensación económica.</p>

    <p class="clausula-titulo">14. DÉCIMA CUARTA: INCUMPLIMIENTOS</p>
    <p>Constituyen incumplimientos graves:</p>
    <ul>
        <li>Divulgación de información confidencial.</li>
        <li>Captación de clientes con beneficio personal de EL COLABORADOR.</li>
        <li>Uso indebido de activos digitales.</li>
        <li>Incumplimiento reiterado de responsabilidades.</li>
        <li>Conductas que afecten la reputación de la empresa.</li>
    </ul>

    <p class="clausula-titulo">15. DÉCIMA QUINTA: RESOLUCIÓN DE CONTROVERSIAS</p>
    <p>Las partes procurarán resolver conflictos mediante diálogo y conciliación antes de acudir a la vía judicial.</p>

    <p class="clausula-titulo">16. DÉCIMA SEXTA: CAPACITACIÓN Y DESARROLLO PROFESIONAL</p>
    <p>LUMEN STRATYX E.I.R.L reconoce el aprendizaje continuo como un pilar fundamental para el crecimiento de la organización y de sus colaboradores.</p>
    <p>
        Los colaboradores podrán solicitar capacitaciones, mentorías, sesiones de
        acompañamiento o actividades formativas cuando éstas resulten necesarias para la
        correcta ejecución de proyectos asignados o para el fortalecimiento de sus competencias
        profesionales.
    </p>
    <p>
        LA EMPRESA evaluará la pertinencia, disponibilidad y viabilidad de dichas solicitudes,
        pudiendo brindar capacitaciones internas, acceso a recursos educativos, talleres,
        documentación especializada o acompañamiento profesional según las necesidades del
        proyecto.
    </p>
    <p>
        Las capacitaciones proporcionadas por LA EMPRESA tendrán como finalidad fortalecer
        las capacidades técnicas, estratégicas y profesionales de los colaboradores, promoviendo
        una cultura de mejora continua, innovación y excelencia.
    </p>

    <p class="clausula-titulo">17. DÉCIMA SÉPTIMA: PROTECCIÓN DE DATOS PERSONALES</p>
    <p>
        Las partes reconocen la importancia de proteger la información personal a la que puedan
        tener acceso durante la ejecución de proyectos, comprometiéndose a cumplir con las
        disposiciones establecidas en la Ley N.° 29733, Ley de Protección de Datos Personales,
        su Reglamento y demás normas complementarias que resulten aplicables.
    </p>
    <p>
        EL COLABORADOR se obliga a utilizar los datos personales de clientes, prospectos,
        proveedores, colaboradores o terceros únicamente para el cumplimiento de las
        actividades autorizadas por LUMEN STRATYX E.I.R.L., quedando prohibida cualquier
        utilización distinta a los fines del proyecto o servicio asignado.
    </p>
    <p>Asimismo, EL COLABORADOR se compromete a:</p>
    <ul>
        <li>a) Mantener la confidencialidad de toda información personal a la que tenga acceso.</li>
        <li>b) No copiar, descargar, almacenar, compartir, transferir o divulgar datos personales sin autorización expresa de LA EMPRESA.</li>
        <li>c) Implementar medidas razonables de seguridad para proteger la información bajo su responsabilidad.</li>
        <li>d) Informar inmediatamente a LA EMPRESA sobre cualquier incidente, pérdida, acceso no autorizado, filtración o riesgo que pueda comprometer la seguridad de los datos personales.</li>
        <li>e) Devolver o eliminar la información personal proporcionada por LA EMPRESA cuando concluya su participación en el proyecto o cuando ésta lo solicite.</li>
    </ul>
    <p>La obligación de confidencialidad y protección de datos permanecerá vigente incluso después de la terminación del presente convenio.</p>
    <p>
        El incumplimiento de las obligaciones establecidas en la presente cláusula podrá dar
        lugar a la resolución inmediata del convenio, sin perjuicio de las acciones legales,
        administrativas o indemnizatorias que correspondan conforme a la legislación vigente.
    </p>

    <p class="declaracion-titulo">DECLARACIÓN INSTITUCIONAL</p>
    <p>
        LUMEN STRATYX reconoce y promueve el crecimiento profesional de sus
        colaboradores, fomentando la construcción ética de sus portafolios, el aprendizaje
        continuo y el desarrollo de oportunidades dentro del ecosistema digital.
    </p>

    <div class="firmas">
        <table>
            <tr>
                <td>
                    <div class="firma-caja">@if ($empresaFirma)<img src="{{ $empresaFirma }}" alt="Firma LA EMPRESA" class="firma-img">@endif</div>
                    <div class="firma-linea">
                        LUMEN STRATYX E.I.R.L.<br>
                        Gerente General
                    </div>
                </td>
                <td>
                    <div class="firma-caja">@if ($employee->firmaBase64())<img src="{{ $employee->firmaBase64() }}" alt="Firma colaborador" class="firma-img">@endif</div>
                    <div class="firma-linea">
                        {{ $employee->fullName() }}<br>
                        COLABORADOR(A)
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <p style="text-align:center; font-style: italic; margin-top: 20px;">Firmado el {{ $generatedAt->translatedFormat('d \d\e F \d\e Y') }}.</p>

    <p class="footer">Documento generado automáticamente por {{ config('app.name') }} el {{ $generatedAt->format('d/m/Y H:i') }}.</p>
</body>
</html>
