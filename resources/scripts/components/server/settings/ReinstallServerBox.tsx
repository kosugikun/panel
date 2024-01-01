import React, { useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import reinstallServer from '@/api/server/reinstallServer';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import tw from 'twin.macro';
import { Button } from '@/components/elements/button/index';
import { Dialog } from '@/components/elements/dialog';

export default () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const [modalVisible, setModalVisible] = useState(false);
    const { addFlash, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const reinstall = () => {
        clearFlashes('settings');
        reinstallServer(uuid)
            .then(() => {
                addFlash({
                    key: 'settings',
                    type: 'success',
                    message: 'サーバーの再インストールが開始されました。',
                });
            })
            .catch((error) => {
                console.error(error);

                addFlash({ key: 'settings', type: 'error', message: httpErrorToHuman(error) });
            })
            .then(() => setModalVisible(false));
    };

    useEffect(() => {
        clearFlashes();
    }, []);

    return (
        <TitledGreyBox title={'サーバーの再インストール'} css={tw`relative`}>
            <Dialog.Confirm
                open={modalVisible}
                title={'サーバーの再インストールを確認'}
                confirm={'はい、サーバーを再インストール'}
                onClose={() => setModalVisible(false)}
                onConfirmed={reinstall}
            >
                サーバーは停止され、このプロセス中に一部のファイルが削除または変更される可能性があります。続行してもよろしいですか？
            </Dialog.Confirm>
            <p css={tw`text-sm`}>
                サーバーを再インストールすると、それが停止し、初期に設定したインストールスクリプトが再実行されます。&nbsp;
                <strong css={tw`font-medium`}>
                    このプロセス中に一部のファイルが削除または変更される可能性がありますので、続行する前にデータをバックアップしてください。
                </strong>
            </p>
            <div css={tw`mt-6 text-right`}>
                <Button.Danger variant={Button.Variants.Secondary} onClick={() => setModalVisible(true)}>
                    サーバーを再インストール
                </Button.Danger>
            </div>
        </TitledGreyBox>
    );
};
