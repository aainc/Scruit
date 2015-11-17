<?php
/**
 * Date: 15/11/09
 * Time: 18:03.
 */

namespace Scruit\subsets\task;


use Scruit\database\Table;

class BuildXMLGenerator implements Generatable
{

    private $appName = null;

    public function __construct($appName)
    {
        $this->appName = $appName;
    }
    public function getTaskName()
    {
        return 'build';
    }

    public function getContents(array $schemes)
    {
        ob_start();
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
?>
<project name="<?php echo htmlspecialchars($this->appName, ENT_QUOTES)?>" default="phpunit" basedir=".">
    <property name="basedir" value="." />
    <property name="test.dir" value="${basedir}/tests" />
    <property name="reports.dir" value="${basedir}/../reports" />
    <property name="vendor.dir" value="${basedir}/vendor" />
    <property name="app.dir" value="${basedir}/app" />
    <property name="test.report.dir" value="${reports.dir}/test" />
    <property name="coverage.report.dir" value="${reports.dir}/coverage" />
    <property name="doc.dir" value="${reports.dir}/doc" />
    <target name="build" depends="prepare, phpcpd, phpmd, phpcs, phpunit, phpdoc, coverage"/>

    <target name="vendor_update" description="update libraries via composer">
        <if>
            <available file="${vendor.dir}" type="dir" />
            <!--<then></then>-->
            <else>
                <composer composer="${basedir}/composer.phar" command="update"></composer>
            </else>
        </if>
    </target>

    <target name="deploy" description="">
        <composer composer="${basedir}/composer.phar" command="install">
            <arg value="--optimize-autoloader" />
            <arg value="--no-dev" />
        </composer>
        <!-- TODO: デプロイの方式を決める -->
    </target>

    <!-- レポートなどを格納するフォルダ作成する処理-->
    <target name="prepare" description="prepare">
        <phingcall target="clean" />
        <phingcall target="vendor_update" />
        <mkdir dir="${coverage.report.dir}" />
        <mkdir dir="${reports.dir}" />
        <mkdir dir="${test.report.dir}" />
        <mkdir dir="${doc.dir}" />
    </target>


    <!-- 既存フォルダ削除する処理　-->
    <target name="clean" description="clean">
        <delete dir="${coverage.report.dir}" />
        <delete dir="${reports.dir}" />
        <delete dir="${test.report.dir}" />
        <delete dir="${doc.dir}" />
    </target>

    <!-- PHPCS -->
    <target name="phpcs" description= "Generate phpcs.xml using PHP_CodeSniffer" >
        <exec executable= "${vendor.dir}/bin/phpcs" output= "${reports.dir}/phpcs.xml" >
            <arg line= "
            --report=checkstyle
            --standard=PSR2
            --extensions=php
            ${app.dir}" />
        </exec>
    </target>

    <!-- PHPMD -->
    <target name="phpmd" >
        <exec command="${vendor.dir}/bin/phpmd ${app.dir} xml codesize,design,unusedcode --reportfile=${reports.dir}/phpcs.xml"></exec>
    </target>

    <target name="phpcpd">
        <exec dir="." command="${vendor.dir}/bin/phpcpd ${basedir}/app --log-pmd=${reports.dir}/cpd.xml"/>
    </target>

    <target name="phpunit">
        <exec dir="." command="${vendor.dir}/bin/phpunit --log-junit ${test.report.dir}/phpunit.log ${basedir}/tests " checkreturn="true"  logoutput="/dev/stdout" />
    </target>

    <target name="coverage">
        <exec dir="${basedir}" command="${vendor.dir}/bin/phpunit  --coverage-html ${coverage.report.dir}" logoutput="/dev/stdout" />
    </target>

    <target name="integration-test">
        <exec dir="../" command="php scruit integration-test" logoutput="/dev/stdout" />
    </target>

    <target name="phpdoc">
        <delete dir="${doc.dir}" includeemptydirs="true" />
        <mkdir dir="${doc.dir}" />
        <exec dir="." logoutput="/dev/stdout" command="
            ${vendor.dir}/bin/phpdoc
            -d ${app.dir}
            -t ${doc.dir}
        "/>
    </target>
</project>
<?php
        return new TaskResult('build.xml', ob_get_clean());
    }
}